<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators;

use Closure;
use Illuminate\Support\Facades\File;

class FileWriter
{
    private bool $force = false;
    private ?CrudLogger $logger = null;
    private ?Closure $confirmOverwrite = null;

    public function configure(bool $force, ?CrudLogger $logger = null, ?Closure $confirmOverwrite = null): void
    {
        $this->force = $force;
        $this->logger = $logger;
        $this->confirmOverwrite = $confirmOverwrite;
    }

    public function write(string $path, string $content): bool
    {
        if (File::exists($path)) {
            if (!$this->force) {
                $this->logger?->warn('  → ' . basename($path) . ' (skipped — already exists)');
                return false;
            }

            if ($this->confirmOverwrite && !($this->confirmOverwrite)($path)) {
                $this->logger?->warn('  → ' . basename($path) . ' (skipped)');
                return false;
            }
        }

        $dir = dirname($path);

        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put($path, $content);

        return true;
    }
}
