<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators\Builders;

class ControllerMethodBuilder
{
    private const ALL_METHODS = ['index', 'show', 'store', 'update', 'destroy'];

    public function build(string $model, array $methods = []): string
    {
        $allowed = empty($methods) ? self::ALL_METHODS : array_intersect(self::ALL_METHODS, $methods);
        $var     = lcfirst($model);
        $output  = '';

        foreach ($allowed as $method) {
            $output .= $this->renderMethod($method, $model, $var) . "\n";
        }

        return rtrim($output);
    }

    private function renderMethod(string $method, string $model, string $var): string
    {
        $doc = $this->phpDoc($method, $model, $var);

        return match ($method) {
            'index'   => <<<PHP
{$doc}    public function index(): AnonymousResourceCollection
    {
        return {$model}Resource::collection(
            \$this->service->index()
        );
    }
PHP,
            'show'    => <<<PHP
{$doc}    public function show({$model} \${$var}): {$model}Resource
    {
        return new {$model}Resource(
            \$this->service->show(\${$var})
        );
    }
PHP,
            'store'   => <<<PHP
{$doc}    public function store({$model}StoreRequest \$request): {$model}Resource
    {
        return new {$model}Resource(
            \$this->service->store(\$request->validated())
        );
    }
PHP,
            'update'  => <<<PHP
{$doc}    public function update({$model}UpdateRequest \$request, {$model} \${$var}): {$model}Resource
    {
        return new {$model}Resource(
            \$this->service->update(\${$var}, \$request->validated())
        );
    }
PHP,
            'destroy' => <<<PHP
{$doc}    public function destroy({$model} \${$var}): Response
    {
        \$this->service->delete(\${$var});

        return response()->noContent();
    }
PHP,
            default   => '',
        };
    }

    private function phpDoc(string $method, string $model, string $var): string
    {
        if (!config('crud-generator.generate_php_docs', false)) {
            return '';
        }

        $docs = match ($method) {
            'index'   => ['return' => 'AnonymousResourceCollection'],
            'show'    => ['params' => ["{$model} \${$var}"], 'return' => "{$model}Resource"],
            'store'   => ['params' => ["{$model}StoreRequest \$request"], 'return' => "{$model}Resource"],
            'update'  => ['params' => ["{$model}UpdateRequest \$request", "{$model} \${$var}"], 'return' => "{$model}Resource"],
            'destroy' => ['params' => ["{$model} \${$var}"], 'return' => 'Response'],
            default   => [],
        };

        $lines = ['    /**'];
        foreach ($docs['params'] ?? [] as $param) {
            $lines[] = "     * @param {$param}";
        }
        if (isset($docs['return'])) {
            $lines[] = "     * @return {$docs['return']}";
        }
        $lines[] = '     */';

        return implode("\n", $lines) . "\n";
    }
}