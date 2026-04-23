<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators\Builders;

use Vendor\LaravelAttributeCodeGenerators\Generators\RelationDetector;

class ServiceMethodBuilder
{
    public function __construct(
        private RelationDetector $detector = new RelationDetector()
    ) {}

    public function build(string $modelClass): string
    {
        $model = class_basename($modelClass);

        $relations = $this->detector->detect($modelClass);

        $loadArray = $this->buildLoadArray($relations);

        return $this->render($model, $loadArray);
    }

    public function buildInterface(string $modelClass): string
    {
        $model = class_basename($modelClass);

        return <<<PHP
    public function index();

    public function show({$model} \$model);

    public function store(array \$data);

    public function update({$model} \$model, array \$data);

    public function delete({$model} \$model);
PHP;
    }

    private function render(string $model, string $loadArray): string
    {
        return <<<PHP
    public function index()
    {
        return \$this->repository->paginate();
    }

    public function show({$model} \$model)
    {
        return \$model->load([{$loadArray}]);
    }

    public function store(array \$data)
    {
        return \$this->repository->create(\$data);
    }

    public function update({$model} \$model, array \$data)
    {
        return \$this->repository->update(\$model, \$data);
    }

    public function delete({$model} \$model)
    {
        \$this->repository->delete(\$model);
    }
PHP;
    }

    private function buildLoadArray(array $relations): string
    {
        if (empty($relations)) {
            return '';
        }

        return implode(
            ', ',
            array_map(fn($rel) => "'{$rel['name']}'", $relations)
        );
    }
}
