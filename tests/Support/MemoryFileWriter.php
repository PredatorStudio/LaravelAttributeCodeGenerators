<?php

namespace Tests\Support;

use Vendor\LaravelAttributeCodeGenerators\Generators\FileWriter;

class MemoryFileWriter extends FileWriter
{
    public array $written = [];

    public function write(string $path, string $content): bool
    {
        $this->written[$path] = $content;
        return true;
    }

    public function has(string $pathSuffix): bool
    {
        foreach (array_keys($this->written) as $path) {
            if (str_ends_with($path, $pathSuffix)) {
                return true;
            }
        }
        return false;
    }

    public function get(string $pathSuffix): ?string
    {
        foreach ($this->written as $path => $content) {
            if (str_ends_with($path, $pathSuffix)) {
                return $content;
            }
        }
        return null;
    }

    public function all(): array
    {
        return $this->written;
    }
}
