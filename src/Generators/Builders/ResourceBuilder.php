<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators\Builders;

use Vendor\LaravelAttributeCodeGenerators\Attributes\Resource;

class ResourceBuilder
{
    public function build(?Resource $attribute): string
    {
        if ($attribute === null || empty($attribute->fields)) {
            return '        return parent::toArray($request);';
        }

        $lines = "        return [\n";
        foreach ($attribute->fields as $field) {
            $lines .= "            '{$field}' => \$this->{$field},\n";
        }
        $lines .= '        ];';

        return $lines;
    }
}
