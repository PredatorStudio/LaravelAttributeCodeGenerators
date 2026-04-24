<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators;

final class FieldFilter
{
    private const SYSTEM_COLUMNS = ['id', 'created_at', 'updated_at', 'deleted_at'];

    public static function isSystemColumn(string $name): bool
    {
        return $name === '' || in_array($name, self::SYSTEM_COLUMNS, true);
    }

    public static function isHidden(array $field): bool
    {
        return !empty($field['hidden']);
    }
}
