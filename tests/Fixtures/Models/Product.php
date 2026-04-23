<?php

namespace Tests\Fixtures\Models;

use Vendor\LaravelAttributeCodeGenerators\Attributes\Crud;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Repository;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Resource;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Service;

#[Crud]
#[Resource(fields: ['id', 'name'])]
#[Service(interface: true)]
#[Repository(interface: true)]
class Product
{
}
