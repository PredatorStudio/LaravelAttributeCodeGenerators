<?php

namespace Tests\Fixtures\Models;

class ModelForModification
{
    public function fields(): array
    {
        return [
            ['name' => 'id',        'type' => 'id'],
            ['name' => 'title',     'type' => 'string'],
            ['name' => 'content',   'type' => 'text', 'nullable' => true],
            ['name' => 'author_id', 'type' => 'foreignId'],
        ];
    }
}
