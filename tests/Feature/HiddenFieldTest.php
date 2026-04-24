<?php

namespace Tests\Feature;

use Mockery;
use Tests\Fixtures\Models\Post;
use Tests\Support\MemoryFileWriter;
use Tests\Support\MemoryGenerationManifest;
use Tests\Support\MemoryModelModifier;
use Tests\Support\MemoryRouteCollector;
use Tests\TestCase;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Resource as ResourceAttr;
use Vendor\LaravelAttributeCodeGenerators\Generators\BindingsCollector;
use Vendor\LaravelAttributeCodeGenerators\Generators\Builders\ResourceBuilder;
use Vendor\LaravelAttributeCodeGenerators\Generators\CrudGenerator;
use Vendor\LaravelAttributeCodeGenerators\Generators\FileWriter;
use Vendor\LaravelAttributeCodeGenerators\Generators\GenerationManifest;
use Vendor\LaravelAttributeCodeGenerators\Generators\MigrationGenerator;
use Vendor\LaravelAttributeCodeGenerators\Generators\MigrationParser;
use Vendor\LaravelAttributeCodeGenerators\Generators\ModelModifier;
use Vendor\LaravelAttributeCodeGenerators\Generators\ModelProcessor;
use Vendor\LaravelAttributeCodeGenerators\Generators\ModelScanner;
use Vendor\LaravelAttributeCodeGenerators\Generators\RouteCollector;
use Vendor\LaravelAttributeCodeGenerators\Generators\RuleGenerator;

class HiddenFieldTest extends TestCase
{
    private MemoryFileWriter $fileWriter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileWriter = new MemoryFileWriter();

        $this->app->instance(FileWriter::class, $this->fileWriter);
        $this->app->instance(RouteCollector::class, new MemoryRouteCollector());
        $this->app->instance(ModelModifier::class, new MemoryModelModifier());
        $this->app->instance(GenerationManifest::class, new MemoryGenerationManifest());

        $migrationGenerator = Mockery::mock(MigrationGenerator::class);
        $migrationGenerator->shouldIgnoreMissing();
        $this->app->instance(MigrationGenerator::class, $migrationGenerator);

        $scanner = Mockery::mock(ModelScanner::class);
        $scanner->shouldReceive('scan')->andReturn([Post::class]);
        $this->app->instance(ModelScanner::class, $scanner);

        $parser = Mockery::mock(MigrationParser::class);
        $parser->shouldReceive('parse')->with('post')->andReturn([
            ['name' => 'title',       'type' => 'string',  'nullable' => false, 'unique' => false, 'foreign' => false],
            ['name' => 'body',        'type' => 'text',    'nullable' => false, 'unique' => false, 'foreign' => false],
            ['name' => 'secret_hash', 'type' => 'string',  'nullable' => false, 'unique' => false, 'foreign' => false],
        ]);
        $this->app->instance(MigrationParser::class, $parser);

        $this->app->make(CrudGenerator::class)->generateAll();
    }

    // --- RuleGenerator unit tests ---

    public function test_rule_generator_excludes_hidden_fields(): void
    {
        $generator = new RuleGenerator();
        $columns   = [
            ['name' => 'title',       'type' => 'string', 'nullable' => false, 'unique' => false, 'foreign' => false],
            ['name' => 'secret_hash', 'type' => 'string', 'nullable' => false, 'unique' => false, 'foreign' => false],
        ];

        $rules = $generator->generate($columns, 'posts', ['secret_hash']);

        $this->assertArrayHasKey('title', $rules);
        $this->assertArrayNotHasKey('secret_hash', $rules);
    }

    public function test_rule_generator_ignores_empty_column_name(): void
    {
        $generator = new RuleGenerator();
        $columns   = [
            ['name' => '',     'type' => 'id',     'nullable' => false, 'unique' => false, 'foreign' => false],
            ['name' => '',     'type' => 'timestamps', 'nullable' => false, 'unique' => false, 'foreign' => false],
            ['name' => 'name', 'type' => 'string', 'nullable' => false, 'unique' => false, 'foreign' => false],
        ];

        $rules = $generator->generate($columns, 'posts');

        $this->assertArrayNotHasKey('', $rules);
        $this->assertArrayHasKey('name', $rules);
    }

    // --- ResourceBuilder unit tests ---

    public function test_resource_builder_excludes_hidden_fields_in_auto_mode(): void
    {
        $builder = new ResourceBuilder();
        $body    = $builder->build(new ResourceAttr(), Post::class);

        $this->assertStringContainsString("'title'", $body);
        $this->assertStringContainsString("'body'", $body);
        $this->assertStringNotContainsString("'secret_hash'", $body);
    }

    public function test_resource_builder_auto_generates_when_resource_has_no_explicit_fields(): void
    {
        $builder = new ResourceBuilder();
        $body    = $builder->build(new ResourceAttr(), Post::class);

        $this->assertStringNotContainsString('parent::toArray', $body);
        $this->assertStringContainsString('return [', $body);
    }

    public function test_resource_builder_falls_back_to_parent_when_no_resource_attribute(): void
    {
        $builder = new ResourceBuilder();
        $body    = $builder->build(null);

        $this->assertStringContainsString('parent::toArray', $body);
    }

    public function test_resource_builder_falls_back_to_parent_when_model_has_no_fields_method(): void
    {
        $builder = new ResourceBuilder();
        $body    = $builder->build(new ResourceAttr());

        $this->assertStringContainsString('parent::toArray', $body);
    }

    public function test_resource_builder_uses_explicit_fields_over_auto_generation(): void
    {
        $builder  = new ResourceBuilder();
        $body     = $builder->build(new ResourceAttr(fields: ['id', 'title']), Post::class);

        $this->assertStringContainsString("'id'", $body);
        $this->assertStringContainsString("'title'", $body);
        $this->assertStringNotContainsString("'body'", $body);
    }

    // --- Integration: StoreRequest excludes hidden field ---

    public function test_store_request_does_not_contain_hidden_field(): void
    {
        $content = $this->fileWriter->get('Http/Requests/PostStoreRequest.php');

        $this->assertNotNull($content, 'PostStoreRequest.php was not generated');
        $this->assertStringNotContainsString("'secret_hash'", $content);
    }

    public function test_store_request_contains_visible_fields(): void
    {
        $content = $this->fileWriter->get('Http/Requests/PostStoreRequest.php');

        $this->assertStringContainsString("'title'", $content);
        $this->assertStringContainsString("'body'", $content);
    }

    // --- Integration: Resource excludes hidden field ---

    public function test_resource_does_not_contain_hidden_field(): void
    {
        $content = $this->fileWriter->get('Http/Resources/PostResource.php');

        $this->assertNotNull($content, 'PostResource.php was not generated');
        $this->assertStringNotContainsString("'secret_hash'", $content);
    }

    public function test_resource_contains_visible_fields(): void
    {
        $content = $this->fileWriter->get('Http/Resources/PostResource.php');

        $this->assertStringContainsString("'title'", $content);
        $this->assertStringContainsString("'body'", $content);
    }
}
