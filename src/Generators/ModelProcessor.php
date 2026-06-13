<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators;

use Vendor\LaravelAttributeCodeGenerators\Attributes\BackedEnum;
use Vendor\LaravelAttributeCodeGenerators\Generators\Builders\ActionBuilder;
use Vendor\LaravelAttributeCodeGenerators\Generators\Builders\ControllerMethodBuilder;
use Vendor\LaravelAttributeCodeGenerators\Generators\Builders\DtoBuilder;
use Vendor\LaravelAttributeCodeGenerators\Generators\Builders\FactoryBuilder;
use Vendor\LaravelAttributeCodeGenerators\Generators\Builders\ResourceBuilder;
use Vendor\LaravelAttributeCodeGenerators\Generators\Builders\ServiceMethodBuilder;

class ModelProcessor
{
    public function __construct(
        private AttributeReader $reader,
        private StubRenderer $renderer,
        private FileWriter $writer,
        private MigrationParser $migrationParser,
        private RuleGenerator $ruleGenerator,
        private MigrationGenerator $migrationGenerator,
        private EnumGenerator $enumGenerator,
        private CrudLogger $logger,
        private ModelModifier $modifier,
        private ApiDocsGenerator $apiDocsGenerator,
        private ApiDocsCollector $apiDocsCollector,
    ) {}

    public function plan(string $modelClass): array
    {
        $data = $this->reader->read($modelClass);

        if (!$data['crud']) {
            return [];
        }

        $model   = $data['shortName'];
        $planned = ["{$model}Controller", "{$model}Resource"];

        if ($data['service'])                  $planned[] = "{$model}Service";
        if ($data['service']?->interface)      $planned[] = "{$model}ServiceInterface";
        if ($data['repository'])               $planned[] = "{$model}Repository";
        if ($data['repository']?->interface)   $planned[] = "{$model}RepositoryInterface";
        if ($data['policy'])                   $planned[] = "{$model}Policy";
        if ($data['validateFromMigration']) {
            $planned[] = "{$model}StoreRequest";
            $planned[] = "{$model}UpdateRequest";
        }
        if ($data['dto'])              $planned[] = "{$model}DTO";
        if ($data['generateMigration']) $planned[] = 'migration';
        if ($data['observer'])         $planned[] = "{$model}Observer";
        if ($data['action']) {
            $planned[] = "Create{$model}Action";
            $planned[] = "Update{$model}Action";
            $planned[] = "Delete{$model}Action";
        }
        if ($data['factory']) $planned[] = "{$model}Factory";
        if ($data['seeder'])  $planned[] = "{$model}Seeder";
        foreach ($data['backedEnums'] as $enumAttr) {
            $planned[] = $enumAttr->filename ?? ($model . ucfirst($enumAttr->field));
        }
        if ($data['generateTest']) $planned[] = "{$model}Test";
        if ($data['apiDocs'])      $planned[] = "{$model}.yaml";

        if (method_exists($modelClass, 'fields') || $data['softDeletes'] || !empty($data['backedEnums'])) {
            $planned[] = "{$model}.php (model sync)";
        }

        return $planned;
    }

    public function process(string $modelClass, RouteCollector $routes, BindingsCollector $bindings, GenerationManifest $manifest): array
    {
        $data = $this->reader->read($modelClass);

        if (!$data['crud']) {
            $this->logger->warn("  Skipped — no #[Crud] attribute");
            return [];
        }

        $model     = $data['shortName'];
        $subPath   = $this->modelSubdir($modelClass);
        $generated = [];

        $skip = fn(string $artifact) => $manifest->isAlreadyGenerated($model, $artifact);

        $route = $this->resolveRoute($data, $model);

        if (!$skip("{$model}Controller") && $this->generateController($model, $data, $subPath)) {
            $this->logger->line("  → {$model}Controller.php");
            $generated[] = "{$model}Controller";
        }

        if (!$skip("{$model}Resource") && $this->generateResource($model, $modelClass, $data, $subPath)) {
            $this->logger->line("  → {$model}Resource.php");
            $generated[] = "{$model}Resource";
        }

        if ($data['service'] && !$skip("{$model}Service") && $this->generateService($model, $modelClass, $data, $subPath)) {
            $this->logger->line("  → {$model}Service.php");
            $generated[] = "{$model}Service";
        }

        if ($data['service']?->interface && !$skip("{$model}ServiceInterface") && $this->generateServiceInterface($model, $modelClass, $subPath)) {
            $this->logger->line("  → {$model}ServiceInterface.php");
            $generated[] = "{$model}ServiceInterface";
            $contractsNs = $this->resolveNamespace('contracts', 'app/Contracts', $subPath);
            $servicesNs  = $this->resolveNamespace('services', 'app/Services', $subPath);
            $bindings->add("{$contractsNs}\\{$model}ServiceInterface", "{$servicesNs}\\{$model}Service");
        }

        if ($data['repository'] && !$skip("{$model}Repository") && $this->generateRepository($model, $data, $subPath)) {
            $this->logger->line("  → {$model}Repository.php");
            $generated[] = "{$model}Repository";
        }

        if ($data['repository']?->interface && !$skip("{$model}RepositoryInterface") && $this->generateRepositoryInterface($model, $data, $subPath)) {
            $this->logger->line("  → {$model}RepositoryInterface.php");
            $generated[] = "{$model}RepositoryInterface";
            $contractsNs    = $this->resolveNamespace('contracts', 'app/Contracts', $subPath);
            $repositoriesNs = $this->resolveNamespace('repositories', 'app/Repositories', $subPath);
            $bindings->add("{$contractsNs}\\{$model}RepositoryInterface", "{$repositoriesNs}\\{$model}Repository");
        }

        if ($data['policy'] && !$skip("{$model}Policy") && $this->generatePolicy($model, $data, $subPath)) {
            $this->logger->line("  → {$model}Policy.php");
            $generated[] = "{$model}Policy";
        }

        if ($data['validateFromMigration']) {
            if (!$skip("{$model}StoreRequest") || !$skip("{$model}UpdateRequest")) {
                [$storeWritten, $updateWritten] = $this->generateRequests($model, $modelClass, $data, $skip, $subPath);
                if ($storeWritten) {
                    $this->logger->line("  → {$model}StoreRequest.php");
                    $generated[] = "{$model}StoreRequest";
                }
                if ($updateWritten) {
                    $this->logger->line("  → {$model}UpdateRequest.php");
                    $generated[] = "{$model}UpdateRequest";
                }
            }
        }

        if ($data['dto'] && !$skip("{$model}DTO") && $this->generateDTO($model, $modelClass, $subPath)) {
            $this->logger->line("  → {$model}DTO.php");
            $generated[] = "{$model}DTO";
        }

        if ($data['generateMigration']) {
            array_push($generated, ...$this->processMigration($model, $modelClass, $data, $skip, $manifest));
        }

        if ($data['observer'] && !$skip("{$model}Observer") && $this->generateObserver($model, $subPath)) {
            $this->logger->line("  → {$model}Observer.php");
            $generated[] = "{$model}Observer";
        }

        if ($data['action']) {
            [$createWritten, $updateWritten, $deleteWritten] = $this->generateActions($model, $skip, $subPath);
            if ($createWritten) {
                $this->logger->line("  → Create{$model}Action.php");
                $generated[] = "Create{$model}Action";
            }
            if ($updateWritten) {
                $this->logger->line("  → Update{$model}Action.php");
                $generated[] = "Update{$model}Action";
            }
            if ($deleteWritten) {
                $this->logger->line("  → Delete{$model}Action.php");
                $generated[] = "Delete{$model}Action";
            }
        }

        if ($data['factory'] && !$skip("{$model}Factory") && $this->generateFactory($model, $modelClass)) {
            $this->logger->line("  → {$model}Factory.php");
            $generated[] = "{$model}Factory";
        }

        if ($data['seeder'] && !$skip("{$model}Seeder") && $this->generateSeeder($model, $data)) {
            $this->logger->line("  → {$model}Seeder.php");
            $generated[] = "{$model}Seeder";
        }

        foreach ($data['backedEnums'] as $enumAttr) {
            $enumName = $enumAttr->filename ?? ($model . ucfirst($enumAttr->field));
            if (!$skip($enumName) && $this->generateEnum($enumName, $enumAttr, $subPath)) {
                $this->logger->line("  → {$enumName}.php");
                $generated[] = $enumName;
            }
        }

        if ($data['generateTest'] && !$skip("{$model}Test") && $this->generateFeatureTest($model, $route)) {
            $this->logger->line("  → {$model}Test.php");
            $generated[] = "{$model}Test";
        }

        $modelSyncKey = "{$model} (model sync)";
        if (!$skip($modelSyncKey) && $this->modifier->modify($modelClass, $data)) {
            $this->logger->line("  → {$model}.php (model synced)");
            $generated[] = $modelSyncKey;
        }

        if ($data['apiDocs'] && $this->apiDocsGenerator->generate($model, $data, $route)) {
            $this->logger->line("  → {$model}.yaml");
            $generated[] = "{$model}.yaml";
            $this->apiDocsCollector->add($model, $route, $data['crud']->methods ?: [], $data['apiDocs']->description);
        }

        $controllerFqn = $this->resolveNamespace('controllers', 'app/Http/Controllers', $subPath) . "\\{$model}Controller";
        $routes->add($route, $controllerFqn, $data['crud']->methods ?? [], $data['route']?->middleware ?? []);

        return $generated;
    }

    // -------------------------------------------------------------------------
    // Generators
    // -------------------------------------------------------------------------

    private function generateController(string $model, array $data, string $subPath): bool
    {
        $subNs        = $this->subNamespace($subPath);
        $useInterface = $data['service']?->interface;
        $contractsNs  = $this->resolveNamespace('contracts', 'app/Contracts', $subPath);
        $servicesNs   = $this->resolveNamespace('services', 'app/Services', $subPath);

        return $this->writer->write(
            $this->resolvePath('controllers', 'app/Http/Controllers', $subPath, "{$model}Controller.php"),
            $this->renderer->render(
                $this->stubPath('controller.stub'),
                [
                    'model'         => $model,
                    'namespace'     => $this->resolveNamespace('controllers', 'app/Http/Controllers', $subPath),
                    'sub_namespace' => $subNs,
                    'service_fqn'   => $useInterface ? "{$contractsNs}\\{$model}ServiceInterface" : "{$servicesNs}\\{$model}Service",
                    'service_type'  => $useInterface ? "{$model}ServiceInterface" : "{$model}Service",
                    'methods'       => (new ControllerMethodBuilder())->build($model, $data['crud']->methods ?? []),
                ]
            )
        );
    }

    private function generateResource(string $model, string $modelClass, array $data, string $subPath): bool
    {
        return $this->writer->write(
            $this->resolvePath('resources', 'app/Http/Resources', $subPath, "{$model}Resource.php"),
            $this->renderer->render(
                $this->stubPath('resource.stub'),
                [
                    'model'         => $model,
                    'namespace'     => $this->resolveNamespace('resources', 'app/Http/Resources', $subPath),
                    'sub_namespace' => $this->subNamespace($subPath),
                    'body'          => (new ResourceBuilder())->build($data['resource'], $modelClass),
                ]
            )
        );
    }

    private function generateService(string $model, string $modelClass, array $data, string $subPath): bool
    {
        $subNs         = $this->subNamespace($subPath);
        $repoInterface = $data['repository']?->interface;
        $selfInterface = $data['service']?->interface;
        $contractsNs   = $this->resolveNamespace('contracts', 'app/Contracts', $subPath);
        $reposNs       = $this->resolveNamespace('repositories', 'app/Repositories', $subPath);

        return $this->writer->write(
            $this->resolvePath('services', 'app/Services', $subPath, "{$model}Service.php"),
            $this->renderer->render(
                $this->stubPath('service.stub'),
                [
                    'model'                 => $model,
                    'namespace'             => $this->resolveNamespace('services', 'app/Services', $subPath),
                    'sub_namespace'         => $subNs,
                    'methods'               => (new ServiceMethodBuilder())->build($modelClass),
                    'repository_fqn'        => $repoInterface ? "{$contractsNs}\\{$model}RepositoryInterface" : "{$reposNs}\\{$model}Repository",
                    'repository_type'       => $repoInterface ? "{$model}RepositoryInterface" : "{$model}Repository",
                    'service_interface_use' => $selfInterface ? "\nuse {$contractsNs}\\{$model}ServiceInterface;" : '',
                    'implements'            => $selfInterface ? " implements {$model}ServiceInterface" : '',
                ]
            )
        );
    }

    private function generateRepository(string $model, array $data, string $subPath): bool
    {
        $subNs         = $this->subNamespace($subPath);
        $selfInterface = $data['repository']?->interface;
        $contractsNs   = $this->resolveNamespace('contracts', 'app/Contracts', $subPath);

        return $this->writer->write(
            $this->resolvePath('repositories', 'app/Repositories', $subPath, "{$model}Repository.php"),
            $this->renderer->render(
                $this->stubPath('repository.stub'),
                [
                    'model'                    => $model,
                    'namespace'                => $this->resolveNamespace('repositories', 'app/Repositories', $subPath),
                    'sub_namespace'            => $subNs,
                    'soft_deletes'             => $data['softDeletes'] ? $this->softDeleteMethods($model) : '',
                    'repository_interface_use' => $selfInterface ? "\nuse {$contractsNs}\\{$model}RepositoryInterface;" : '',
                    'implements'               => $selfInterface ? " implements {$model}RepositoryInterface" : '',
                ]
            )
        );
    }

    private function softDeleteMethods(string $model): string
    {
        return <<<PHP

    public function restore({$model} \$model): void
    {
        \$model->restore();
    }

    public function forceDelete({$model} \$model): void
    {
        \$model->forceDelete();
    }

PHP;
    }

    private function generateServiceInterface(string $model, string $modelClass, string $subPath): bool
    {
        return $this->writer->write(
            $this->resolvePath('contracts', 'app/Contracts', $subPath, "{$model}ServiceInterface.php"),
            $this->renderer->render(
                $this->stubPath('contract-service.stub'),
                [
                    'model'         => $model,
                    'namespace'     => $this->resolveNamespace('contracts', 'app/Contracts', $subPath),
                    'sub_namespace' => $this->subNamespace($subPath),
                    'methods'       => (new ServiceMethodBuilder())->buildInterface($modelClass),
                ]
            )
        );
    }

    private function generateRepositoryInterface(string $model, array $data, string $subPath): bool
    {
        return $this->writer->write(
            $this->resolvePath('contracts', 'app/Contracts', $subPath, "{$model}RepositoryInterface.php"),
            $this->renderer->render(
                $this->stubPath('contract-repository.stub'),
                [
                    'model'         => $model,
                    'namespace'     => $this->resolveNamespace('contracts', 'app/Contracts', $subPath),
                    'sub_namespace' => $this->subNamespace($subPath),
                    'soft_deletes'  => $data['softDeletes'] ? $this->softDeleteInterfaceMethods($model) : '',
                ]
            )
        );
    }

    private function softDeleteInterfaceMethods(string $model): string
    {
        return <<<PHP

    public function restore({$model} \$model): void;

    public function forceDelete({$model} \$model): void;

PHP;
    }

    private function generatePolicy(string $model, array $data, string $subPath): bool
    {
        return $this->writer->write(
            $this->resolvePath('policies', 'app/Policies', $subPath, "{$model}Policy.php"),
            $this->renderer->render(
                $this->stubPath('policy.stub'),
                [
                    'model'         => $model,
                    'namespace'     => $this->resolveNamespace('policies', 'app/Policies', $subPath),
                    'sub_namespace' => $this->subNamespace($subPath),
                ]
            )
        );
    }

    private function generateRequests(string $model, string $modelClass, array $data, callable $skip, string $subPath): array
    {
        $columns      = $this->migrationParser->parse(strtolower($model));
        $hiddenFields = $this->getHiddenFields($modelClass);
        $table        = strtolower($model);

        $storeRules  = $this->ruleGenerator->generate($columns, $table, $hiddenFields);
        $updateRules = $this->ruleGenerator->generate($columns, $table, $hiddenFields, true);

        return [
            !$skip("{$model}StoreRequest")  ? $this->writeRequest($model, 'store',  $storeRules,  $subPath) : false,
            !$skip("{$model}UpdateRequest") ? $this->writeRequest($model, 'update', $updateRules, $subPath) : false,
        ];
    }

    private function writeRequest(string $model, string $type, array $rules, string $subPath): bool
    {
        return $this->writer->write(
            $this->resolvePath('requests', 'app/Http/Requests', $subPath, "{$model}" . ucfirst($type) . "Request.php"),
            $this->renderer->render(
                $this->stubPath("{$type}-request.stub"),
                [
                    'model'     => $model,
                    'namespace' => $this->resolveNamespace('requests', 'app/Http/Requests', $subPath),
                    'rules'     => $this->formatRules($rules),
                ]
            )
        );
    }

    private function formatRules(array $rules): string
    {
        $output = '';

        foreach ($rules as $field => $ruleArray) {
            $ruleString = implode("','", $ruleArray);
            $output    .= "            '{$field}' => ['{$ruleString}'],\n";
        }

        return rtrim($output);
    }

    private function generateDTO(string $model, string $modelClass, string $subPath): bool
    {
        return $this->writer->write(
            $this->resolvePath('dto', 'app/DTO', $subPath, "{$model}DTO.php"),
            (new DtoBuilder())->build($modelClass)
        );
    }

    private function generateObserver(string $model, string $subPath): bool
    {
        return $this->writer->write(
            $this->resolvePath('observers', 'app/Observers', $subPath, "{$model}Observer.php"),
            $this->renderer->render(
                $this->stubPath('observer.stub'),
                [
                    'model'         => $model,
                    'namespace'     => $this->resolveNamespace('observers', 'app/Observers', $subPath),
                    'sub_namespace' => $this->subNamespace($subPath),
                    'var'           => lcfirst($model),
                ]
            )
        );
    }

    private function generateActions(string $model, callable $skip, string $subPath): array
    {
        $builder  = new ActionBuilder();
        $actionsNs = $this->resolveNamespace('actions', 'app/Actions', $subPath);
        $modelsNs  = $this->resolveModelNamespace($subPath);

        return [
            !$skip("Create{$model}Action") ? $this->writer->write(
                $this->resolvePath('actions', 'app/Actions', $subPath, "Create{$model}Action.php"),
                $builder->build($model, $actionsNs, $modelsNs)
            ) : false,
            !$skip("Update{$model}Action") ? $this->writer->write(
                $this->resolvePath('actions', 'app/Actions', $subPath, "Update{$model}Action.php"),
                $builder->buildUpdate($model, $actionsNs, $modelsNs)
            ) : false,
            !$skip("Delete{$model}Action") ? $this->writer->write(
                $this->resolvePath('actions', 'app/Actions', $subPath, "Delete{$model}Action.php"),
                $builder->buildDelete($model, $actionsNs, $modelsNs)
            ) : false,
        ];
    }

    private function generateFactory(string $model, string $modelClass): bool
    {
        return $this->writer->write(
            database_path("factories/{$model}Factory.php"),
            (new FactoryBuilder())->build($modelClass)
        );
    }

    private function generateSeeder(string $model, array $data): bool
    {
        return $this->writer->write(
            database_path("seeders/{$model}Seeder.php"),
            $this->renderer->render(
                $this->stubPath('seeder.stub'),
                [
                    'model' => $model,
                    'count' => (string) $data['seeder']->count,
                ]
            )
        );
    }

    private function generateEnum(string $enumName, BackedEnum $attr, string $subPath): bool
    {
        return $this->writer->write(
            $this->resolvePath('enums', 'app/Enums', $subPath, "{$enumName}.php"),
            $this->enumGenerator->generate($enumName, $attr)
        );
    }

    private function generateFeatureTest(string $model, string $route): bool
    {
        return $this->writer->write(
            base_path("tests/Feature/{$model}Test.php"),
            $this->renderer->render(
                $this->stubPath('feature-test.stub'),
                ['model' => $model, 'var' => lcfirst($model), 'route' => $route]
            )
        );
    }

    // -------------------------------------------------------------------------
    // Path / namespace helpers
    // -------------------------------------------------------------------------

    private function modelSubdir(string $modelClass): string
    {
        $scanPath      = config('crud-generator.scan_path', 'app/Models');
        $baseNamespace = $this->pathToNamespace($scanPath);
        $classParts    = explode('\\', $modelClass);
        $baseParts     = explode('\\', $baseNamespace);
        $extra         = array_slice($classParts, count($baseParts), -1);
        return implode('/', $extra);
    }

    private function resolvePath(string $configKey, string $fallback, string $subPath, string $filename): string
    {
        $base = rtrim(config("crud-generator.paths.{$configKey}", $fallback), '/');
        $dir  = $subPath ? "{$base}/{$subPath}" : $base;
        return base_path("{$dir}/{$filename}");
    }

    private function resolveNamespace(string $configKey, string $fallback, string $subPath): string
    {
        $base = rtrim(config("crud-generator.paths.{$configKey}", $fallback), '/');
        $path = $subPath ? "{$base}/{$subPath}" : $base;
        return $this->pathToNamespace($path);
    }

    private function resolveModelNamespace(string $subPath): string
    {
        $base = rtrim(config('crud-generator.scan_path', 'app/Models'), '/');
        $path = $subPath ? "{$base}/{$subPath}" : $base;
        return $this->pathToNamespace($path);
    }

    private function subNamespace(string $subPath): string
    {
        return $subPath ? '\\' . str_replace('/', '\\', $subPath) : '';
    }

    private function pathToNamespace(string $path): string
    {
        return implode('\\', array_map('ucfirst', explode('/', $path)));
    }

    // -------------------------------------------------------------------------
    // Other helpers
    // -------------------------------------------------------------------------

    private function resolveRoute(array $data, string $model): string
    {
        return $data['route']?->path ?? strtolower($model) . 's';
    }

    private function getHiddenFields(string $modelClass): array
    {
        if (!method_exists($modelClass, 'fields')) {
            return [];
        }
        return array_values(array_column(
            array_filter((new $modelClass)->fields(), fn($f) => !empty($f['hidden'])),
            'name'
        ));
    }

    private function processMigration(string $model, string $modelClass, array $data, callable $skip, GenerationManifest $manifest): array
    {
        $fields             = method_exists($modelClass, 'fields') ? (new $modelClass)->fields() : [];
        $currentColumnNames = $this->extractColumnNames($fields);

        if (!$skip('migration')) {
            $this->migrationGenerator->generate($modelClass, (bool) $data['softDeletes']);
            $manifest->saveMigrationColumns($model, $currentColumnNames);
            $this->logger->line("  → migration file");
            return ['migration'];
        }

        $savedColumnNames = $manifest->loadMigrationColumns($model);
        $newColumnNames   = array_values(array_diff($currentColumnNames, $savedColumnNames));

        if (empty($newColumnNames)) {
            return [];
        }

        $newFields = array_values(array_filter($fields, function (array $field) use ($newColumnNames): bool {
            $name = $field['name'] ?? ($field['type'] === 'id' ? 'id' : null);
            return $name !== null && in_array($name, $newColumnNames, true);
        }));

        $this->migrationGenerator->generateAlter($modelClass, $newFields);
        $manifest->saveMigrationColumns($model, array_merge($savedColumnNames, $newColumnNames));
        $this->logger->line("  → migration file (alter: " . implode(', ', $newColumnNames) . ")");

        return ['migration_alter'];
    }

    private function extractColumnNames(array $fields): array
    {
        $names = [];
        foreach ($fields as $field) {
            if (isset($field['name'])) {
                $names[] = $field['name'];
            } elseif (($field['type'] ?? '') === 'id') {
                $names[] = 'id';
            } elseif (($field['type'] ?? '') === 'primary' && isset($field['columns'])) {
                foreach ($field['columns'] as $col) {
                    $names[] = $col;
                }
            }
        }
        return array_values(array_unique($names));
    }

    private function stubPath(string $name): string
    {
        return __DIR__ . '/../stubs/' . $name;
    }
}