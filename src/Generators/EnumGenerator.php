<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators;

use Vendor\LaravelAttributeCodeGenerators\Attributes\BackedEnum;

class EnumGenerator
{
    public function generate(string $enumName, BackedEnum $attr): string
    {
        $className = $attr->filename ?? $enumName;
        $cases     = '';

        foreach ($attr->values as $value) {
            $caseName = ucfirst($value);
            $cases   .= "    case {$caseName} = '{$value}';\n";
        }

        return <<<PHP
<?php

namespace App\Enums;

enum {$className}: {$attr->type}
{
{$cases}}
PHP;
    }
}
