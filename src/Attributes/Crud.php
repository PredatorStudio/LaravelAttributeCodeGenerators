<?php
namespace Vendor\LaravelAttributeCodeGenerators\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Crud
{
    public function __construct(
        public array $methods = []
    ) {}
}
