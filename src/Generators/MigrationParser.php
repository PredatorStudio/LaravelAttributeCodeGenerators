<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators;

use Illuminate\Filesystem\Filesystem;

class MigrationParser
{
    public function parse(string $table): array
    {
        $files = app(Filesystem::class)->files(database_path('migrations'));

        $columns = [];

        foreach ($files as $file) {
            $content = file_get_contents($file->getPathname());

            if (!str_contains($content, "create('$table'")) {
                continue;
            }

            $columns = array_merge($columns, $this->extractColumns($content));
        }

        return $columns;
    }

    private function extractColumns(string $content): array
    {
        // s flag (DOTALL) allows matching across multiple lines
        preg_match_all(
            '/\$table->(\w+)\(([^)]*)\)((?:->[\w]+\([^)]*\))*)\s*;/s',
            $content,
            $matches,
            PREG_SET_ORDER
        );

        $columns = [];

        foreach ($matches as $match) {
            $columns[] = $this->mapColumn(
                $match[1],
                $match[2],
                $match[3] ?? ''
            );
        }

        return $columns;
    }

    private function mapColumn(string $type, string $name, string $modifiers): array
    {
        $name = trim($name, "'\" ");

        return [
            'name' => $name,
            'type' => $type,
            'nullable' => str_contains($modifiers, 'nullable'),
            'unique' => str_contains($modifiers, 'unique'),
            'foreign' => str_contains($type, 'foreignId'),
        ];
    }
}
