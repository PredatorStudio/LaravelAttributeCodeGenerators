<?php

namespace Vendor\LaravelAttributeCodeGenerators\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Resource
{
    public function __construct(
        public array $fields = []
    ) {}
}
