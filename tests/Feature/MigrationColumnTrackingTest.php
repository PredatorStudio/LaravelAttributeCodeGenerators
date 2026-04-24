<?php

namespace Tests\Feature;

use Mockery;
use Tests\Fixtures\Models\Article;
use Tests\Support\MemoryFileWriter;
use Tests\Support\MemoryGenerationManifest;
use Tests\Support\MemoryModelModifier;
use Tests\Support\MemoryRouteCollector;
use Tests\TestCase;
use Vendor\LaravelAttributeCodeGenerators\Generators\BindingsCollector;
use Vendor\LaravelAttributeCodeGenerators\Generators\FileWriter;
use Vendor\LaravelAttributeCodeGenerators\Generators\GenerationManifest;
use Vendor\LaravelAttributeCodeGenerators\Generators\MigrationGenerator;
use Vendor\LaravelAttributeCodeGenerators\Generators\MigrationParser;
use Vendor\LaravelAttributeCodeGenerators\Generators\ModelModifier;
use Vendor\LaravelAttributeCodeGenerators\Generators\ModelProcessor;
use Vendor\LaravelAttributeCodeGenerators\Generators\RouteCollector;

class MigrationColumnTrackingTest extends TestCase
{
    private MemoryGenerationManifest $manifest;
    private MigrationGenerator $migrationGenerator;
    private MemoryRouteCollector $routes;
    private BindingsCollector $bindings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manifest           = new MemoryGenerationManifest();
        $this->migrationGenerator = Mockery::mock(MigrationGenerator::class);
        $this->routes             = new MemoryRouteCollector();
        $this->bindings           = new BindingsCollector();

        $this->app->instance(FileWriter::class, new MemoryFileWriter());
        $this->app->instance(RouteCollector::class, $this->routes);
        $this->app->instance(ModelModifier::class, new MemoryModelModifier());
        $this->app->instance(GenerationManifest::class, $this->manifest);
        $this->app->instance(MigrationGenerator::class, $this->migrationGenerator);

        $parser = Mockery::mock(MigrationParser::class);
        $parser->shouldReceive('parse')->andReturn([]);
        $this->app->instance(MigrationParser::class, $parser);
    }

    private function processor(): ModelProcessor
    {
        return $this->app->make(ModelProcessor::class);
    }

    // --- GenerationManifest column helpers ---

    public function test_save_and_load_migration_columns(): void
    {
        $this->manifest->saveMigrationColumns('Article', ['id', 'title', 'body']);

        $this->assertSame(['id', 'title', 'body'], $this->manifest->loadMigrationColumns('Article'));
    }

    public function test_load_migration_columns_returns_empty_when_not_saved(): void
    {
        $this->assertSame([], $this->manifest->loadMigrationColumns('Article'));
    }

    public function test_save_migration_columns_replaces_previous_entry(): void
    {
        $this->manifest->saveMigrationColumns('Article', ['id', 'title']);
        $this->manifest->saveMigrationColumns('Article', ['id', 'title', 'body']);

        $this->assertSame(['id', 'title', 'body'], $this->manifest->loadMigrationColumns('Article'));
    }

    public function test_save_migration_columns_preserves_other_artifacts(): void
    {
        $this->manifest->merge('Article', ['ArticleController', 'ArticleResource']);
        $this->manifest->saveMigrationColumns('Article', ['id', 'title']);

        $this->assertContains('ArticleController', $this->manifest->load('Article'));
        $this->assertContains('ArticleResource', $this->manifest->load('Article'));
    }

    // --- ModelProcessor integration ---

    public function test_first_run_generates_create_migration(): void
    {
        $this->migrationGenerator->shouldReceive('generate')->once()->with(Article::class, false);

        $generated = $this->processor()->process(Article::class, $this->routes, $this->bindings, $this->manifest);

        $this->assertContains('migration', $generated);
    }

    public function test_first_run_saves_column_names_in_manifest(): void
    {
        $this->migrationGenerator->shouldReceive('generate')->once();

        $this->processor()->process(Article::class, $this->routes, $this->bindings, $this->manifest);

        $this->assertSame(['id', 'title', 'body'], $this->manifest->loadMigrationColumns('Article'));
    }

    public function test_second_run_with_same_columns_skips_migration(): void
    {
        $this->manifest->merge('Article', ['migration']);
        $this->manifest->saveMigrationColumns('Article', ['id', 'title', 'body']);

        $this->migrationGenerator->shouldNotReceive('generate');
        $this->migrationGenerator->shouldNotReceive('generateAlter');

        $generated = $this->processor()->process(Article::class, $this->routes, $this->bindings, $this->manifest);

        $this->assertNotContains('migration', $generated);
        $this->assertNotContains('migration_alter', $generated);
    }

    public function test_second_run_with_new_columns_generates_alter_migration(): void
    {
        // Simulate first run saved only 'id' and 'title' — 'body' is new
        $this->manifest->merge('Article', ['migration']);
        $this->manifest->saveMigrationColumns('Article', ['id', 'title']);

        $this->migrationGenerator->shouldNotReceive('generate');
        $this->migrationGenerator->shouldReceive('generateAlter')
            ->once()
            ->with(Article::class, Mockery::on(function (array $fields): bool {
                return count($fields) === 1 && $fields[0]['name'] === 'body';
            }));

        $generated = $this->processor()->process(Article::class, $this->routes, $this->bindings, $this->manifest);

        $this->assertContains('migration_alter', $generated);
    }

    public function test_alter_migration_updates_saved_columns(): void
    {
        $this->manifest->merge('Article', ['migration']);
        $this->manifest->saveMigrationColumns('Article', ['id', 'title']);

        $this->migrationGenerator->shouldReceive('generateAlter')->once();

        $this->processor()->process(Article::class, $this->routes, $this->bindings, $this->manifest);

        $saved = $this->manifest->loadMigrationColumns('Article');
        $this->assertContains('id', $saved);
        $this->assertContains('title', $saved);
        $this->assertContains('body', $saved);
    }

    public function test_alter_generates_only_new_columns_not_existing(): void
    {
        $this->manifest->merge('Article', ['migration']);
        $this->manifest->saveMigrationColumns('Article', ['id', 'title']);

        $capturedFields = [];
        $this->migrationGenerator->shouldReceive('generateAlter')
            ->once()
            ->withArgs(function (string $class, array $fields) use (&$capturedFields): bool {
                $capturedFields = $fields;
                return true;
            });

        $this->processor()->process(Article::class, $this->routes, $this->bindings, $this->manifest);

        $names = array_column($capturedFields, 'name');
        $this->assertNotContains('id', $names);
        $this->assertNotContains('title', $names);
        $this->assertContains('body', $names);
    }
}
