<?php

namespace Tests\Unit\Builders;

use PHPUnit\Framework\TestCase;
use Vendor\LaravelAttributeCodeGenerators\Generators\Builders\ControllerMethodBuilder;

class ControllerMethodBuilderTest extends TestCase
{
    private ControllerMethodBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new ControllerMethodBuilder();
    }

    public function test_generates_index_method(): void
    {
        $output = $this->builder->build('Post');

        $this->assertStringContainsString('public function index()', $output);
        $this->assertStringContainsString('PostResource::collection', $output);
    }

    public function test_generates_show_method_with_model_type_hint(): void
    {
        $output = $this->builder->build('Post');

        $this->assertStringContainsString('public function show(Post $post)', $output);
        $this->assertStringContainsString('new PostResource', $output);
    }

    public function test_generates_store_method_with_request(): void
    {
        $output = $this->builder->build('Post');

        $this->assertStringContainsString('public function store(PostStoreRequest $request)', $output);
        $this->assertStringContainsString('$request->validated()', $output);
    }

    public function test_generates_update_method_with_request_and_model(): void
    {
        $output = $this->builder->build('Post');

        $this->assertStringContainsString('public function update(PostUpdateRequest $request, Post $post)', $output);
    }

    public function test_generates_destroy_method(): void
    {
        $output = $this->builder->build('Post');

        $this->assertStringContainsString('public function destroy(Post $post)', $output);
        $this->assertStringContainsString('response()->noContent()', $output);
    }

    public function test_variable_name_is_lowercase_model_name(): void
    {
        $output = $this->builder->build('BlogPost');

        $this->assertStringContainsString('BlogPost $blogPost', $output);
    }
}
