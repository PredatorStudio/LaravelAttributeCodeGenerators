<?php

namespace Vendor\LaravelAttributeCodeGenerators\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Seeder
{
    public function __construct(
        public int $count = 10
    ) {}
}
