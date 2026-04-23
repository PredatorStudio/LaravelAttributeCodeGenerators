<?php

namespace Vendor\LaravelAttributeCodeGenerators\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class DTO
{
    public function __construct(
        public bool $fromRequest = true,
        public bool $immutable = true
    ) {}
}
