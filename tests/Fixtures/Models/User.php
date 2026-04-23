<?php

namespace Tests\Fixtures\Models;

use Vendor\LaravelAttributeCodeGenerators\Attributes\Action;
use Vendor\LaravelAttributeCodeGenerators\Attributes\BackedEnum;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Crud;
use Vendor\LaravelAttributeCodeGenerators\Attributes\DTO;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Factory;
use Vendor\LaravelAttributeCodeGenerators\Attributes\GenerateTest;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Observer;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Policy;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Repository;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Resource;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Route;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Service;
use Vendor\LaravelAttributeCodeGenerators\Attributes\SoftDeletes;
use Vendor\LaravelAttributeCodeGenerators\Attributes\ValidateFromMigration;

#[Crud(methods: ['index', 'store', 'show', 'update', 'destroy'])]
#[Route(path: 'users')]
#[Resource(fields: ['id', 'name', 'email'])]
#[Service]
#[Repository]
#[Policy]
#[ValidateFromMigration]
#[DTO]
#[SoftDeletes]
#[BackedEnum(field: 'status', values: ['active', 'inactive'])]
#[Observer]
#[Action]
#[Factory]
#[GenerateTest]
class User
{
    public function fields(): array
    {
        return [
            ['name' => 'id',     'type' => 'id'],
            ['name' => 'name',   'type' => 'string'],
            ['name' => 'email',  'type' => 'string', 'unique' => true],
            ['name' => 'bio',    'type' => 'text',   'nullable' => true],
        ];
    }
}
