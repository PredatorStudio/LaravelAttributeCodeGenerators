<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators\Builders;

use Vendor\LaravelAttributeCodeGenerators\Attributes\Resource;

class ResourceBuilder
{
    public function build(?Resource $attribute, string $modelClass = ''): string
    {
        if ($attribute !== null && !empty($attribute->fields)) {
            return $this->buildLines($attribute->fields);
        }

        if ($attribute !== null && $modelClass !== '' && method_exists($modelClass, 'fields')) {
            $visible = $this->visibleFieldNames($modelClass);
            if (!empty($visible)) {
                return $this->buildLines($visible);
            }
        }

        return '        return parent::toArray($request);';
    }

    private function buildLines(array $fields): string
    {
        $lines = "        return [\n";
        foreach ($fields as $field) {
            $lines .= "            '{$field}' => \$this->{$field},\n";
        }
        return $lines . '        ];';
    }

    private function visibleFieldNames(string $modelClass): array
    {
        return array_values(array_filter(
            array_column(
                array_filter((new $modelClass)->fields(), fn($f) => empty($f['hidden']) && isset($f['name'])),
                'name'
            )
        ));
    }
}
