<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Vendor\LaravelAttributeCodeGenerators\Generators\RouteCollector;

class RouteCollectorTest extends TestCase
{
    private RouteCollector $collector;

    protected function setUp(): void
    {
        $this->collector = new RouteCollector();
    }

    public function test_route_without_middleware_has_no_middleware_call(): void
    {
        $this->collector->add('posts', 'PostController', ['index', 'show']);

        $output = $this->collector->buildOutput();

        $this->assertStringContainsString("Route::apiResource('posts', PostController::class)", $output);
        $this->assertStringNotContainsString('middleware', $output);
    }

    public function test_route_with_single_middleware(): void
    {
        $this->collector->add('posts', 'PostController', ['index'], ['auth:sanctum']);

        $output = $this->collector->buildOutput();

        $this->assertStringContainsString("->middleware(['auth:sanctum'])", $output);
    }

    public function test_route_with_multiple_middleware(): void
    {
        $this->collector->add('posts', 'PostController', [], ['auth:sanctum', 'verified']);

        $output = $this->collector->buildOutput();

        $this->assertStringContainsString("->middleware(['auth:sanctum','verified'])", $output);
    }

    public function test_route_with_methods_and_middleware(): void
    {
        $this->collector->add('posts', 'PostController', ['index', 'store'], ['auth:sanctum']);

        $output = $this->collector->buildOutput();

        $this->assertStringContainsString("->only(['index','store'])", $output);
        $this->assertStringContainsString("->middleware(['auth:sanctum'])", $output);
    }

    public function test_route_without_methods_has_no_only_call(): void
    {
        $this->collector->add('posts', 'PostController', []);

        $output = $this->collector->buildOutput();

        $this->assertStringNotContainsString('->only(', $output);
    }
}
