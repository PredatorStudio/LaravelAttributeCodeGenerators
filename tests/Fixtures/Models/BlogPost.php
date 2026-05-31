<?php

namespace Tests\Fixtures\Models;

use Vendor\LaravelAttributeCodeGenerators\Attributes\ApiDocs;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Crud;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Resource;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Route;

#[Crud(methods: ['index', 'store', 'show', 'update', 'destroy'])]
#[Route(path: 'blog-posts')]
#[Resource]
#[ApiDocs(description: 'Blog post entries')]
class BlogPost
{
    public function fields(): array
    {
        return [
            ['name' => 'id',      'type' => 'id'],
            ['name' => 'title',   'type' => 'string'],
            ['name' => 'body',    'type' => 'text'],
            ['name' => 'secret',  'type' => 'string', 'hidden' => true],
        ];
    }
}