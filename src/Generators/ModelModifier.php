<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators;

use Illuminate\Support\Str;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use ReflectionClass;

class ModelModifier
{
    private \PhpParser\Parser $parser;
    private Standard $printer;
    private BuilderFactory $factory;

    public function __construct()
    {
        $this->parser  = (new ParserFactory())->createForNewestSupportedVersion();
        $this->printer = new Standard();
        $this->factory = new BuilderFactory();
    }

    public function modify(string $modelClass, array $data): bool
    {
        $reflection = new ReflectionClass($modelClass);
        $filePath   = $reflection->getFileName();

        if (!$filePath || !is_file($filePath)) {
            return false;
        }

        $source    = file_get_contents($filePath);
        $oldStmts  = $this->parser->parse($source);
        $oldTokens = $this->parser->getTokens();

        $traverser = new NodeTraverser(new CloningVisitor());
        $newStmts  = $traverser->traverse($oldStmts);

        $namespaceNode = $this->findNamespace($newStmts);
        $contextStmts  = $namespaceNode?->stmts ?? $newStmts;
        $classNode     = $this->findClass($contextStmts, $reflection->getShortName());

        if (!$classNode) {
            return false;
        }

        $fields  = $this->getModelFields($modelClass);
        $changed = false;

        $changed = $this->addFillable($classNode, $fields) || $changed;
        $changed = $this->addCasts($classNode, $namespaceNode, $data) || $changed;
        $changed = $this->addSoftDeletes($classNode, $namespaceNode, $data) || $changed;
        $changed = $this->addRelations($classNode, $namespaceNode, $fields) || $changed;

        if (!$changed) {
            return false;
        }

        $newCode = $this->printer->printFormatPreserving($newStmts, $oldStmts, $oldTokens);
        $this->writeFile($filePath, $newCode);

        return true;
    }

    protected function writeFile(string $path, string $content): void
    {
        file_put_contents($path, $content);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private function getModelFields(string $modelClass): array
    {
        if (!method_exists($modelClass, 'fields')) {
            return [];
        }

        try {
            $instance = (new ReflectionClass($modelClass))->newInstanceWithoutConstructor();
            return $instance->fields();
        } catch (\Throwable) {
            return [];
        }
    }

    private function findNamespace(array $stmts): ?Node\Stmt\Namespace_
    {
        foreach ($stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Namespace_) {
                return $stmt;
            }
        }
        return null;
    }

    private function findClass(array $stmts, string $name): ?Node\Stmt\Class_
    {
        foreach ($stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Class_ && $stmt->name?->toString() === $name) {
                return $stmt;
            }
        }
        return null;
    }

    private function hasProperty(Node\Stmt\Class_ $class, string $name): bool
    {
        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Property) {
                foreach ($stmt->props as $prop) {
                    if ($prop->name->toString() === $name) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function hasTraitUse(Node\Stmt\Class_ $class, string $shortName): bool
    {
        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\TraitUse) {
                foreach ($stmt->traits as $trait) {
                    if ($trait->getLast() === $shortName) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function hasMethod(Node\Stmt\Class_ $class, string $name): bool
    {
        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassMethod && $stmt->name->toString() === $name) {
                return true;
            }
        }
        return false;
    }

    // -------------------------------------------------------------------------
    // Modification methods
    // -------------------------------------------------------------------------

    private function addFillable(Node\Stmt\Class_ $classNode, array $fields): bool
    {
        if ($this->hasProperty($classNode, 'fillable')) {
            return false;
        }

        $idFields = array_column(
            array_filter($fields, fn($f) => in_array($f['type'] ?? '', ['id', 'primary'])),
            'name'
        );

        $fillableNames = array_values(array_filter(
            array_map(fn($f) => $f['name'] ?? null, $fields),
            fn($n) => $n !== null && !in_array($n, array_filter($idFields))
        ));

        if (empty($fillableNames)) {
            return false;
        }

        $items = array_map(
            fn($n) => new Node\Expr\ArrayItem(new Node\Scalar\String_($n)),
            $fillableNames
        );

        $property = new Node\Stmt\Property(
            Node\Stmt\Class_::MODIFIER_PROTECTED,
            [new Node\PropertyItem(
                new Node\VarLikeIdentifier('fillable'),
                new Node\Expr\Array_($items, ['kind' => Node\Expr\Array_::KIND_SHORT])
            )]
        );

        $stmts = $classNode->stmts;
        array_unshift($stmts, $property);
        $classNode->stmts = $stmts;

        return true;
    }

    private function addCasts(Node\Stmt\Class_ $classNode, ?Node\Stmt\Namespace_ $namespace, array $data): bool
    {
        if ($this->hasProperty($classNode, 'casts') || empty($data['backedEnums'])) {
            return false;
        }

        $model = $data['shortName'];
        $items = [];

        foreach ($data['backedEnums'] as $enumAttr) {
            $enumName = $model . ucfirst($enumAttr->field);
            $items[]  = new Node\Expr\ArrayItem(
                new Node\Expr\ClassConstFetch(
                    new Node\Name($enumName),
                    new Node\Identifier('class')
                ),
                new Node\Scalar\String_($enumAttr->field)
            );

            if ($namespace) {
                $this->ensureUseStatement($namespace, "App\\Enums\\{$enumName}");
            }
        }

        $property = new Node\Stmt\Property(
            Node\Stmt\Class_::MODIFIER_PROTECTED,
            [new Node\PropertyItem(
                new Node\VarLikeIdentifier('casts'),
                new Node\Expr\Array_($items, ['kind' => Node\Expr\Array_::KIND_SHORT])
            )]
        );

        // Insert after $fillable, or at the top if it doesn't exist
        $insertIdx = 0;
        foreach ($classNode->stmts as $i => $stmt) {
            if ($stmt instanceof Node\Stmt\Property) {
                foreach ($stmt->props as $prop) {
                    if ($prop->name->toString() === 'fillable') {
                        $insertIdx = $i + 1;
                        break 2;
                    }
                }
                $insertIdx = $i + 1;
            }
        }

        $stmts = $classNode->stmts;
        array_splice($stmts, $insertIdx, 0, [$property]);
        $classNode->stmts = $stmts;

        return true;
    }

    private function addSoftDeletes(Node\Stmt\Class_ $classNode, ?Node\Stmt\Namespace_ $namespace, array $data): bool
    {
        if (!$data['softDeletes'] || $this->hasTraitUse($classNode, 'SoftDeletes')) {
            return false;
        }

        $traitUse = new Node\Stmt\TraitUse([new Node\Name('SoftDeletes')]);

        // Insert after properties, before methods
        $insertIdx = 0;
        foreach ($classNode->stmts as $i => $stmt) {
            if ($stmt instanceof Node\Stmt\Property) {
                $insertIdx = $i + 1;
            }
        }

        $stmts = $classNode->stmts;
        array_splice($stmts, $insertIdx, 0, [$traitUse]);
        $classNode->stmts = $stmts;

        if ($namespace) {
            $this->ensureUseStatement($namespace, 'Illuminate\\Database\\Eloquent\\SoftDeletes');
        }

        return true;
    }

    private function addRelations(Node\Stmt\Class_ $classNode, ?Node\Stmt\Namespace_ $namespace, array $fields): bool
    {
        $changed = false;

        foreach ($fields as $field) {
            if (($field['type'] ?? '') !== 'foreignId' || empty($field['name'])) {
                continue;
            }

            $relatedModel = Str::studly(preg_replace('/_id$/', '', $field['name']));
            $methodName   = lcfirst($relatedModel);

            if ($this->hasMethod($classNode, $methodName)) {
                continue;
            }

            $method = $this->factory->method($methodName)
                ->makePublic()
                ->setReturnType('BelongsTo')
                ->addStmt(new Node\Stmt\Return_(
                    new Node\Expr\MethodCall(
                        new Node\Expr\Variable('this'),
                        new Node\Identifier('belongsTo'),
                        [new Node\Arg(new Node\Expr\ClassConstFetch(
                            new Node\Name($relatedModel),
                            new Node\Identifier('class')
                        ))]
                    )
                ))
                ->getNode();

            $classNode->stmts[] = $method;

            if ($namespace) {
                $this->ensureUseStatement($namespace, 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo');
            }

            $changed = true;
        }

        return $changed;
    }

    private function ensureUseStatement(Node\Stmt\Namespace_ $namespace, string $fqn): void
    {
        foreach ($namespace->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Use_) {
                foreach ($stmt->uses as $use) {
                    if ($use->name->toString() === $fqn) {
                        return;
                    }
                }
            }
        }

        // Find insertion point: after last Use_ before Class_
        $insertIdx = 0;
        foreach ($namespace->stmts as $i => $stmt) {
            if ($stmt instanceof Node\Stmt\Use_) {
                $insertIdx = $i + 1;
            } elseif ($stmt instanceof Node\Stmt\Class_) {
                break;
            }
        }

        $useNode = new Node\Stmt\Use_([
            new Node\Stmt\UseUse(new Node\Name($fqn))
        ]);

        $stmts = $namespace->stmts;
        array_splice($stmts, $insertIdx, 0, [$useNode]);
        $namespace->stmts = $stmts;
    }
}
