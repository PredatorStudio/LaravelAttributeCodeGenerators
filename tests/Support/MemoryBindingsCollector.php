<?php

namespace Tests\Support;

use Vendor\LaravelAttributeCodeGenerators\Generators\BindingsCollector;

class MemoryBindingsCollector extends BindingsCollector
{
    public string $output = '';

    public function flush(): void
    {
        $this->output = 'flushed';
    }
}
