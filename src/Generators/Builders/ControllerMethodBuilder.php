<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators\Builders;

class ControllerMethodBuilder
{
    public function build(string $model): string
    {
        $var = lcfirst($model);

        return <<<PHP
    public function index()
    {
        return {$model}Resource::collection(
            \$this->service->index()
        );
    }

    public function show({$model} \${$var})
    {
        return new {$model}Resource(
            \$this->service->show(\${$var})
        );
    }

    public function store({$model}StoreRequest \$request)
    {
        return new {$model}Resource(
            \$this->service->store(\$request->validated())
        );
    }

    public function update({$model}UpdateRequest \$request, {$model} \${$var})
    {
        return new {$model}Resource(
            \$this->service->update(\${$var}, \$request->validated())
        );
    }

    public function destroy({$model} \${$var})
    {
        \$this->service->delete(\${$var});

        return response()->noContent();
    }
PHP;
    }
}
