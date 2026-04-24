<?php

namespace Tests\Fixtures\Models;

use Vendor\LaravelAttributeCodeGenerators\Attributes\Crud;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Resource;
use Vendor\LaravelAttributeCodeGenerators\Attributes\ValidateFromMigration;

#[Crud(methods: ['index', 'store', 'show', 'update', 'destroy'])]
#[Resource]
#[ValidateFromMigration]
class Post
{
    public function fields(): array
    {
        return [
            ['name' => 'id',          'type' => 'id'],
            ['name' => 'title',       'type' => 'string'],
            ['name' => 'body',        'type' => 'text'],
            ['name' => 'secret_hash', 'type' => 'string', 'hidden' => true],
        ];
    }
}
