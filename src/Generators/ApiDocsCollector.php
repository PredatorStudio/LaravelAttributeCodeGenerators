<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators;

use Illuminate\Support\Facades\File;

class ApiDocsCollector
{
    private array $entries = [];

    public function add(string $model, string $route, array $methods, string $description = ''): void
    {
        $this->entries[] = compact('model', 'route', 'methods', 'description');
    }

    public function isEmpty(): bool
    {
        return empty($this->entries);
    }

    public function flush(): void
    {
        if (empty($this->entries)) {
            return;
        }

        $path = base_path(config('crud-generator.api_docs_main_file', 'docs/api/openapi.yaml'));
        $dir  = dirname($path);

        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put($path, $this->buildMainYaml());
    }

    private function buildMainYaml(): string
    {
        $yaml  = "openapi: 3.0.0\n";
        $yaml .= "info:\n";
        $yaml .= "  title: API Documentation\n";
        $yaml .= "  version: 1.0.0\n";
        $yaml .= "paths:\n";

        foreach ($this->entries as $entry) {
            $yaml .= $this->buildPathRefs($entry['model'], $entry['route'], $entry['methods']);
        }

        $yaml .= "components:\n";
        $yaml .= "  schemas: {}\n";

        return $yaml;
    }

    private function buildPathRefs(string $model, string $route, array $methods): string
    {
        $var       = lcfirst($model);
        $modelsRef = $this->modelsRelativePath();
        $yaml      = '';

        if (!empty(array_intersect($methods, ['index', 'store']))) {
            $pointer = '~1' . $route;
            $yaml   .= "  /{$route}:\n";
            $yaml   .= "    \$ref: '{$modelsRef}/{$model}.yaml#/paths/{$pointer}'\n";
        }

        if (!empty(array_intersect($methods, ['show', 'update', 'destroy']))) {
            $pointer = '~1' . $route . '~1{' . $var . '}';
            $yaml   .= "  /{$route}/{{$var}}:\n";
            $yaml   .= "    \$ref: '{$modelsRef}/{$model}.yaml#/paths/{$pointer}'\n";
        }

        return $yaml;
    }

    private function modelsRelativePath(): string
    {
        $mainFileConfig  = config('crud-generator.api_docs_main_file', 'docs/api/openapi.yaml');
        $modelsPathConfig = config('crud-generator.api_docs_models_path', 'docs/api/models');

        $mainDir     = dirname($mainFileConfig);
        $modelsDir   = $modelsPathConfig;

        if (str_starts_with($modelsDir, $mainDir . '/')) {
            return './' . substr($modelsDir, strlen($mainDir) + 1);
        }

        return './' . basename($modelsDir);
    }
}