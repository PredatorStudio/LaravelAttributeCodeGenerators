<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators;

use Illuminate\Support\Str;

class RuleGenerator
{
    public function generate(array $columns, string $table): array
    {
        $rules = [];

        foreach ($columns as $col) {
            if ($this->isIgnored($col['name'])) {
                continue;
            }

            $rules[$col['name']] = $this->rulesFor($col, $table);
        }

        return $rules;
    }

    private function rulesFor(array $col, string $table): array
    {
        $rules = [];

        if ($col['nullable']) {
            $rules[] = 'nullable';
        } else {
            $rules[] = 'required';
        }

        match ($col['type']) {
            'string', 'text' => $rules[] = 'string',
            'integer' => $rules[] = 'integer',
            'boolean' => $rules[] = 'boolean',
            default => null,
        };

        if (str_contains($col['name'], 'email')) {
            $rules[] = 'email';
        }

        if ($col['foreign']) {
            $rules[] = 'exists:' . $this->guessTable($col['name']) . ',id';
        }

        return $rules;
    }

    private function isIgnored(string $name): bool
    {
        return in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at']);
    }

    private function guessTable(string $column): string
    {
        return Str::plural(str_replace('_id', '', $column));
    }
}
