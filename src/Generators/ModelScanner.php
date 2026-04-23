<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators;

use Illuminate\Support\Facades\File;

class ModelScanner
{
    public function scan(): array
    {
        $files = File::allFiles(app_path('Models'));

        $models = [];

        foreach ($files as $file) {
            $class = 'App\\Models\\' . $file->getFilenameWithoutExtension();

            if (class_exists($class)) {
                $models[] = $class;
            }
        }

        return $models;
    }
}
