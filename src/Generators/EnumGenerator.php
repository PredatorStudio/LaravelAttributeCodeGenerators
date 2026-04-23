<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators;

use Vendor\LaravelAttributeCodeGenerators\Attributes\BackedEnum;

class EnumGenerator
{
    public function generate(string $enumName, BackedEnum $attr): string
    {
        $cases = '';
        foreach ($attr->values as $value) {
            $caseName = ucfirst($value);
            $cases .= "    case {$caseName} = '{$value}';\n";
        }

        return <<<PHP
<?php

namespace App\Enums;

enum {$enumName}: {$attr->type}
{
{$cases}}
PHP;
    }
}
