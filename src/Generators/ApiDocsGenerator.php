<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators;

use Illuminate\Support\Facades\File;

class ApiDocsGenerator
{
    private const TYPE_MAP = [
        'id'                 => 'integer',
        'bigInteger'         => 'integer',
        'unsignedBigInteger' => 'integer',
        'integer'            => 'integer',
        'tinyInteger'        => 'integer',
        'smallInteger'       => 'integer',
        'boolean'            => 'boolean',
        'float'              => 'number',
        'double'             => 'number',
        'decimal'            => 'number',
        'date'               => 'string',
        'dateTime'           => 'string',
        'timestamp'          => 'string',
        'time'               => 'string',
        'json'               => 'object',
        'jsonb'              => 'object',
        'uuid'               => 'string',
        'enum'               => 'string',
    ];

    private const FORMAT_MAP = [
        'float'     => 'float',
        'double'    => 'double',
        'date'      => 'date',
        'dateTime'  => 'date-time',
        'timestamp' => 'date-time',
        'time'      => 'time',
        'uuid'      => 'uuid',
    ];

    public function generate(string $model, array $data, string $route): bool
    {
        $dir = $this->modelsPath();

        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put("{$dir}/{$model}.yaml", $this->buildYaml($model, $data, $route));

        return true;
    }

    private function buildYaml(string $model, array $data, string $route): string
    {
        $methods     = $data['crud']->methods ?: ['index', 'show', 'store', 'update', 'destroy'];
        $fields      = $this->resolveFields($data);
        $description = $data['apiDocs']->description;

        return $this->buildPathsSection($model, $route, $methods)
            . "\n"
            . $this->buildComponentsSection($model, $methods, $fields, $description);
    }

    private function buildPathsSection(string $model, string $route, array $methods): string
    {
        $var              = lcfirst($model);
        $collectionPath   = "/{$route}";
        $itemPath         = "/{$route}/{{$var}}";
        $collectionNeeded = !empty(array_intersect($methods, ['index', 'store']));
        $itemNeeded       = !empty(array_intersect($methods, ['show', 'update', 'destroy']));

        $yaml = "paths:\n";

        if ($collectionNeeded) {
            $yaml .= "  {$collectionPath}:\n";
            if (in_array('index', $methods)) {
                $yaml .= $this->indexOp($model);
            }
            if (in_array('store', $methods)) {
                $yaml .= $this->storeOp($model);
            }
        }

        if ($itemNeeded) {
            $yaml .= "  {$itemPath}:\n";
            $yaml .= "    parameters:\n";
            $yaml .= "      - name: {$var}\n";
            $yaml .= "        in: path\n";
            $yaml .= "        required: true\n";
            $yaml .= "        schema:\n";
            $yaml .= "          type: integer\n";
            if (in_array('show', $methods)) {
                $yaml .= $this->showOp($model);
            }
            if (in_array('update', $methods)) {
                $yaml .= $this->updateOp($model);
            }
            if (in_array('destroy', $methods)) {
                $yaml .= $this->destroyOp($model);
            }
        }

        return $yaml;
    }

    private function indexOp(string $model): string
    {
        return <<<YAML
    get:
      summary: List {$model}s
      operationId: {$model}-index
      tags:
        - {$model}
      responses:
        '200':
          description: OK
          content:
            application/json:
              schema:
                type: array
                items:
                  \$ref: '#/components/schemas/{$model}'

YAML;
    }

    private function storeOp(string $model): string
    {
        return <<<YAML
    post:
      summary: Create {$model}
      operationId: {$model}-store
      tags:
        - {$model}
      requestBody:
        required: true
        content:
          application/json:
            schema:
              \$ref: '#/components/schemas/{$model}Request'
      responses:
        '201':
          description: Created
          content:
            application/json:
              schema:
                \$ref: '#/components/schemas/{$model}'

YAML;
    }

    private function showOp(string $model): string
    {
        return <<<YAML
    get:
      summary: Get {$model}
      operationId: {$model}-show
      tags:
        - {$model}
      responses:
        '200':
          description: OK
          content:
            application/json:
              schema:
                \$ref: '#/components/schemas/{$model}'

YAML;
    }

    private function updateOp(string $model): string
    {
        return <<<YAML
    put:
      summary: Update {$model}
      operationId: {$model}-update
      tags:
        - {$model}
      requestBody:
        required: true
        content:
          application/json:
            schema:
              \$ref: '#/components/schemas/{$model}UpdateRequest'
      responses:
        '200':
          description: OK
          content:
            application/json:
              schema:
                \$ref: '#/components/schemas/{$model}'
    patch:
      summary: Partial Update {$model}
      operationId: {$model}-patch
      tags:
        - {$model}
      requestBody:
        required: false
        content:
          application/json:
            schema:
              \$ref: '#/components/schemas/{$model}UpdateRequest'
      responses:
        '200':
          description: OK
          content:
            application/json:
              schema:
                \$ref: '#/components/schemas/{$model}'

YAML;
    }

    private function destroyOp(string $model): string
    {
        return <<<YAML
    delete:
      summary: Delete {$model}
      operationId: {$model}-destroy
      tags:
        - {$model}
      responses:
        '204':
          description: No Content

YAML;
    }

    private function buildComponentsSection(string $model, array $methods, array $fields, string $description): string
    {
        $yaml  = "components:\n";
        $yaml .= "  schemas:\n";
        $yaml .= "    {$model}:\n";
        $yaml .= "      type: object\n";

        if ($description !== '') {
            $yaml .= "      description: {$description}\n";
        }

        $yaml .= "      properties:\n";
        $yaml .= $this->schemaProperties($fields, includeId: true);

        if (in_array('store', $methods)) {
            $yaml .= "    {$model}Request:\n";
            $yaml .= "      type: object\n";
            $yaml .= "      properties:\n";
            $yaml .= $this->schemaProperties($fields, includeId: false);
        }

        if (in_array('update', $methods)) {
            $yaml .= "    {$model}UpdateRequest:\n";
            $yaml .= "      type: object\n";
            $yaml .= "      properties:\n";
            $yaml .= $this->schemaProperties($fields, includeId: false);
        }

        return $yaml;
    }

    private function schemaProperties(array $fields, bool $includeId): string
    {
        if (empty($fields)) {
            return "        id:\n          type: integer\n";
        }

        $yaml = '';

        foreach ($fields as $field) {
            if (!empty($field['hidden'])) {
                continue;
            }

            $name = $field['name'] ?? null;

            if ($name === null) {
                continue;
            }

            if (!$includeId && ($name === 'id' || ($field['type'] ?? '') === 'id')) {
                continue;
            }

            $type   = $field['type'] ?? 'string';
            $oaType = self::TYPE_MAP[$type] ?? 'string';
            $format = self::FORMAT_MAP[$type] ?? null;

            $yaml .= "        {$name}:\n";
            $yaml .= "          type: {$oaType}\n";

            if ($format !== null) {
                $yaml .= "          format: {$format}\n";
            }

            if (!empty($field['nullable'])) {
                $yaml .= "          nullable: true\n";
            }
        }

        return $yaml !== '' ? $yaml : "        {}\n";
    }

    private function resolveFields(array $data): array
    {
        $modelClass = $data['class']->getName();

        if (!method_exists($modelClass, 'fields')) {
            return [];
        }

        return (new $modelClass)->fields();
    }

    public function modelsPath(): string
    {
        $path = config('crud-generator.api_docs_models_path', 'docs/api/models');
        return str_starts_with($path, '/') ? $path : base_path($path);
    }
}