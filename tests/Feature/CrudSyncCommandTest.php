<?php

namespace Tests\Feature;

use Mockery;
use Tests\Fixtures\Models\User;
use Tests\Support\MemoryFileWriter;
use Tests\Support\MemoryGenerationManifest;
use Tests\Support\MemoryModelModifier;
use Tests\Support\MemoryRouteCollector;
use Tests\TestCase;
use Vendor\LaravelAttributeCodeGenerators\Generators\FileWriter;
use Vendor\LaravelAttributeCodeGenerators\Generators\GenerationManifest;
use Vendor\LaravelAttributeCodeGenerators\Generators\MigrationParser;
use Vendor\LaravelAttributeCodeGenerators\Generators\ModelModifier;
use Vendor\LaravelAttributeCodeGenerators\Generators\ModelScanner;
use Vendor\LaravelAttributeCodeGenerators\Generators\RouteCollector;

class CrudSyncCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(FileWriter::class, new MemoryFileWriter());
        $this->app->instance(RouteCollector::class, new MemoryRouteCollector());
        $this->app->instance(ModelModifier::class, new MemoryModelModifier());
        $this->app->instance(GenerationManifest::class, new MemoryGenerationManifest());

        $scanner = Mockery::mock(ModelScanner::class);
        $scanner->shouldReceive('scan')->andReturn([User::class]);
        $this->app->instance(ModelScanner::class, $scanner);

        $parser = Mockery::mock(MigrationParser::class);
        $parser->shouldReceive('parse')->with('user')->andReturn([
            ['name' => 'name',  'type' => 'string', 'nullable' => false, 'unique' => false, 'foreign' => false],
            ['name' => 'email', 'type' => 'string', 'nullable' => false, 'unique' => true,  'foreign' => false],
        ]);
        $this->app->instance(MigrationParser::class, $parser);
    }

    public function test_command_exits_successfully(): void
    {
        $this->artisan('crud:sync')->assertExitCode(0);
    }

    public function test_command_prints_found_models(): void
    {
        $this->artisan('crud:sync')->expectsOutputToContain('Found 1 model');
    }

    public function test_command_prints_generation_plan(): void
    {
        $this->artisan('crud:sync')->expectsOutputToContain('Generation plan');
    }

    public function test_command_prints_model_name_in_plan(): void
    {
        $this->artisan('crud:sync')->expectsOutputToContain('User');
    }

    public function test_command_prints_done_for_each_model(): void
    {
        $this->artisan('crud:sync')->expectsOutputToContain('User done');
    }

    public function test_command_prints_final_summary(): void
    {
        $this->artisan('crud:sync')->expectsOutputToContain('Generation complete');
    }

    public function test_command_is_registered_with_correct_signature(): void
    {
        $commands = $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->all();

        $this->assertArrayHasKey('crud:sync', $commands);
    }
}
