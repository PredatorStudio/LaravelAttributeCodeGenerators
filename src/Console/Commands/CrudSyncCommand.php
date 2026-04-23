<?php

namespace Vendor\LaravelAttributeCodeGenerators\Console\Commands;

use Illuminate\Console\Command;
use Vendor\LaravelAttributeCodeGenerators\Console\ConsoleCrudLogger;
use Vendor\LaravelAttributeCodeGenerators\Generators\CrudGenerator;
use Vendor\LaravelAttributeCodeGenerators\Generators\CrudLogger;
use Vendor\LaravelAttributeCodeGenerators\Generators\FileWriter;
use Vendor\LaravelAttributeCodeGenerators\Generators\GenerationManifest;

class CrudSyncCommand extends Command
{
    protected $signature = 'crud:sync {--dry-run} {--force}';
    protected $description = 'Generate CRUD structure from PHP Attributes';

    public function handle(): void
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $logger = new ConsoleCrudLogger($this);
        $this->laravel->instance(CrudLogger::class, $logger);

        $confirmCallback = $force
            ? fn(string $path) => $this->confirm(basename($path) . ' already exists. Overwrite?')
            : null;

        $this->laravel->make(FileWriter::class)->configure($force, $logger, $confirmCallback);

        if ($force) {
            $this->laravel->make(GenerationManifest::class)->bypass();
        }

        $this->laravel->make(CrudGenerator::class)->generateAll($dryRun);
    }
}
