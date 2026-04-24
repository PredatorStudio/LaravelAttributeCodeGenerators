<?php

namespace Tests\Fixtures\Models;

use Vendor\LaravelAttributeCodeGenerators\Attributes\Crud;
use Vendor\LaravelAttributeCodeGenerators\Attributes\GenerateMigration;

#[Crud(methods: ['index', 'store', 'show', 'update', 'destroy'])]
#[GenerateMigration]
class Article
{
    public function fields(): array
    {
        return [
            ['name' => 'id',    'type' => 'id'],
            ['name' => 'title', 'type' => 'string'],
            ['name' => 'body',  'type' => 'text'],
        ];
    }
}
