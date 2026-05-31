<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators;

use Illuminate\Support\Facades\File;

class ModelScanner
{
    public function scan(): array
    {
        $scanPath = config('crud-generator.scan_path', 'app/Models');
        $absolutePath = base_path($scanPath);

        if (! is_dir($absolutePath)) {
            return [];
        }

        $baseNamespace = $this->pathToNamespace($scanPath);
        $files = File::allFiles($absolutePath);

        $models = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relative = str_replace(
                [DIRECTORY_SEPARATOR, '/', '.php'],
                ['\\', '\\', ''],
                $file->getRelativePathname()
            );

            $class = $baseNamespace . '\\' . $relative;

            if (class_exists($class)) {
                $models[] = $class;
            }
        }

        return $models;
    }

    private function pathToNamespace(string $path): string
    {
        return implode('\\', array_map('ucfirst', explode('/', $path)));
    }
}