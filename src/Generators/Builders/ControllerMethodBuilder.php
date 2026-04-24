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
        return match ($method) {
            'index'   => <<<PHP
    public function index()
    {
        return {$model}Resource::collection(
            \$this->service->index()
        );
    }
PHP,
            'show'    => <<<PHP
    public function show({$model} \${$var})
    {
        return new {$model}Resource(
            \$this->service->show(\${$var})
        );
    }
PHP,
            'store'   => <<<PHP
    public function store({$model}StoreRequest \$request)
    {
        return new {$model}Resource(
            \$this->service->store(\$request->validated())
        );
    }
PHP,
            'update'  => <<<PHP
    public function update({$model}UpdateRequest \$request, {$model} \${$var})
    {
        return new {$model}Resource(
            \$this->service->update(\${$var}, \$request->validated())
        );
    }
PHP,
            'destroy' => <<<PHP
    public function destroy({$model} \${$var})
    {
        \$this->service->delete(\${$var});

        return response()->noContent();
    }
PHP,
            default   => '',
        };
    }
}
