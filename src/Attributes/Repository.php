<?php
namespace Vendor\LaravelAttributeCodeGenerators\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Repository
{
    public function __construct(public readonly bool $interface = false) {}
}
