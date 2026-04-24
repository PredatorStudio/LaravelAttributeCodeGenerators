<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators\Builders;

use Vendor\LaravelAttributeCodeGenerators\Generators\FieldFilter;

class DtoBuilder
{
    public function build(string $modelClass): string
    {
        $model  = class_basename($modelClass);
        $fields = $this->resolveFields($modelClass);

        $constructorArgs = "        public readonly ?int \$id = null,\n";
        $fromArrayBody   = "            id: \$data['id'] ?? null,\n";
        $toArrayBody     = "            'id' => \$this->id,\n";

        foreach ($fields as $field) {
            $name = $field['name'] ?? null;

            if (!$name || FieldFilter::isSystemColumn($name) || FieldFilter::isHidden($field)) {
                continue;
            }

            $phpType = $this->toPhpType($field['type'] ?? 'string');

            $constructorArgs .= "        public readonly ?{$phpType} \${$name} = null,\n";
            $fromArrayBody   .= "            {$name}: \$data['{$name}'] ?? null,\n";
            $toArrayBody     .= "            '{$name}' => \$this->{$name},\n";
        }

        $constructorArgs = rtrim($constructorArgs, ",\n") . "\n";

        return <<<PHP
<?php

namespace App\DTO;

class {$model}DTO
{
    public function __construct(
{$constructorArgs}    ) {}

    public static function fromArray(array \$data): self
    {
        return new self(
{$fromArrayBody}        );
    }

    public function toArray(): array
    {
        return [
{$toArrayBody}        ];
    }
}
PHP;
    }

    private function resolveFields(string $modelClass): array
    {
        if (class_exists($modelClass) && method_exists($modelClass, 'fields')) {
            return (new $modelClass)->fields();
        }

        return [];
    }

    private function toPhpType(string $migType): string
    {
        return match ($migType) {
            'integer', 'foreignId' => 'int',
            'boolean'              => 'bool',
            'json'                 => 'array',
            default                => 'string',
        };
    }
}
