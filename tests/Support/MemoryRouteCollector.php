<?php

namespace Tests\Support;

use Vendor\LaravelAttributeCodeGenerators\Generators\RouteCollector;

class MemoryRouteCollector extends RouteCollector
{
    public string $output = '';

    public function flush(): void
    {
        $this->output = $this->buildOutput();
    }
}
