<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators;

use Illuminate\Support\Str;

class RuleGenerator
{
    public function generate(array $columns, string $table, array $hiddenFields = [], bool $partialUpdate = false): array
    {
        $rules = [];

        foreach ($columns as $col) {
            if ($this->isIgnored($col['name'], $hiddenFields)) {
                continue;
            }

            $colRules = $this->rulesFor($col, $table);

            if ($partialUpdate) {
                array_unshift($colRules, 'sometimes');
            }

            $rules[$col['name']] = $colRules;
        }

        return $rules;
    }

    private function rulesFor(array $col, string $table): array
    {
        $rules   = [];
        $rules[] = $col['nullable'] ? 'nullable' : 'required';

        match ($col['type']) {
            'string', 'text' => $rules[] = 'string',
            'integer'        => $rules[] = 'integer',
            'boolean'        => $rules[] = 'boolean',
            default          => null,
        };

        if (str_contains($col['name'], 'email')) {
            $rules[] = 'email';
        }

        if ($col['foreign']) {
            $rules[] = 'exists:' . $this->guessTable($col['name']) . ',id';
        }

        return $rules;
    }

    private function isIgnored(string $name, array $hiddenFields): bool
    {
        return FieldFilter::isSystemColumn($name)
            || in_array($name, $hiddenFields, true);
    }

    private function guessTable(string $column): string
    {
        return Str::plural(str_replace('_id', '', $column));
    }
}
