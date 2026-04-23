<?php

namespace Vendor\LaravelAttributeCodeGenerators\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class BackedEnum
{
    public function __construct(
        public string $field,
        public array $values,
        public string $type = 'string',
    ) {}
}
