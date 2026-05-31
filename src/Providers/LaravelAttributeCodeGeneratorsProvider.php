<?php

namespace Vendor\LaravelAttributeCodeGenerators\Providers;

use Illuminate\Support\ServiceProvider;
use Vendor\LaravelAttributeCodeGenerators\Console\Commands\CrudInstallCommand;
use Vendor\LaravelAttributeCodeGenerators\Console\Commands\CrudSyncCommand;
use Vendor\LaravelAttributeCodeGenerators\Generators\ApiDocsCollector;
use Vendor\LaravelAttributeCodeGenerators\Generators\CrudLogger;
use Vendor\LaravelAttributeCodeGenerators\Generators\FileWriter;
use Vendor\LaravelAttributeCodeGenerators\Generators\GenerationManifest;
use Vendor\LaravelAttributeCodeGenerators\Generators\NullCrudLogger;

class LaravelAttributeCodeGeneratorsProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/crud-generator.php', 'crud-generator');

        $this->app->bind(CrudLogger::class, NullCrudLogger::class);
        $this->app->singleton(FileWriter::class);
        $this->app->singleton(GenerationManifest::class);
        $this->app->singleton(ApiDocsCollector::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CrudSyncCommand::class,
                CrudInstallCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../../config/crud-generator.php' => config_path('crud-generator.php'),
            ], 'crud-generator-config');
        }
    }
}
