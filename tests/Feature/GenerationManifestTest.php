<?php

namespace Tests\Feature;

use Mockery;
use Tests\Fixtures\Models\User;
use Tests\Support\MemoryFileWriter;
use Tests\Support\MemoryGenerationManifest;
use Tests\Support\MemoryModelModifier;
use Tests\Support\MemoryRouteCollector;
use Tests\TestCase;
use Vendor\LaravelAttributeCodeGenerators\Generators\CrudGenerator;
use Vendor\LaravelAttributeCodeGenerators\Generators\FileWriter;
use Vendor\LaravelAttributeCodeGenerators\Generators\GenerationManifest;
use Vendor\LaravelAttributeCodeGenerators\Generators\MigrationParser;
use Vendor\LaravelAttributeCodeGenerators\Generators\ModelModifier;
use Vendor\LaravelAttributeCodeGenerators\Generators\ModelScanner;
use Vendor\LaravelAttributeCodeGenerators\Generators\RouteCollector;

class GenerationManifestTest extends TestCase
{
    private MemoryFileWriter $fileWriter;
    private MemoryGenerationManifest $manifest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileWriter = new MemoryFileWriter();
        $this->manifest   = new MemoryGenerationManifest();

        $this->app->instance(FileWriter::class, $this->fileWriter);
        $this->app->instance(RouteCollector::class, new MemoryRouteCollector());
        $this->app->instance(ModelModifier::class, new MemoryModelModifier());
        $this->app->instance(GenerationManifest::class, $this->manifest);

        $scanner = Mockery::mock(ModelScanner::class);
        $scanner->shouldReceive('scan')->andReturn([User::class]);
        $this->app->instance(ModelScanner::class, $scanner);

        $parser = Mockery::mock(MigrationParser::class);
        $parser->shouldReceive('parse')->with('user')->andReturn([]);
        $this->app->instance(MigrationParser::class, $parser);
    }

    public function test_manifest_is_populated_after_generation(): void
    {
        $this->app->make(CrudGenerator::class)->generateAll();

        $store = $this->manifest->getStore();

        $this->assertArrayHasKey('User', $store);
        $this->assertContains('UserController', $store['User']);
    }

    public function test_manifest_contains_resource(): void
    {
        $this->app->make(CrudGenerator::class)->generateAll();

        $this->assertContains('UserResource', $this->manifest->getStore()['User']);
    }

    public function test_already_generated_artifact_is_skipped(): void
    {
        // Pre-populate manifest so UserController is already marked as done
        $this->manifest->merge('User', ['UserController']);

        $this->app->make(CrudGenerator::class)->generateAll();

        // FileWriter should NOT have been asked to write UserController again
        $this->assertFalse($this->fileWriter->has('Http/Controllers/UserController.php'));
    }

    public function test_bypass_disables_manifest_checks(): void
    {
        // Pre-populate manifest with everything so normally nothing would be generated
        $this->manifest->merge('User', ['UserController', 'UserResource', 'User (model sync)']);
        $this->manifest->bypass();

        $this->app->make(CrudGenerator::class)->generateAll();

        // With bypass, the controller should be written despite being in the manifest
        $this->assertTrue($this->fileWriter->has('Http/Controllers/UserController.php'));
    }

    public function test_manifest_accumulates_across_runs(): void
    {
        // Simulate first run having generated UserController
        $this->manifest->merge('User', ['UserController']);

        $this->app->make(CrudGenerator::class)->generateAll();

        // After second run, manifest should still contain UserController
        $this->assertContains('UserController', $this->manifest->getStore()['User']);
        // And have added UserResource too
        $this->assertContains('UserResource', $this->manifest->getStore()['User']);
    }
}
