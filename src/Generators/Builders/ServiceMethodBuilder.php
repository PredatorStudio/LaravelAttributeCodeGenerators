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
        $model    = class_basename($modelClass);
        $relations = $this->detector->detect($modelClass);
        $loadArray = $this->buildLoadArray($relations);

        return $this->render($model, $loadArray);
    }

    public function buildInterface(string $modelClass): string
    {
        $model = class_basename($modelClass);

        return $this->renderInterface($model);
    }

    private function render(string $model, string $loadArray): string
    {
        $docs = config('crud-generator.generate_php_docs', false);
        $var  = lcfirst($model);

        $indexDoc   = $docs ? "    /**\n     * @return \\Illuminate\\Contracts\\Pagination\\LengthAwarePaginator\n     */\n" : '';
        $showDoc    = $docs ? "    /**\n     * @param {$model} \${$var}\n     * @return {$model}\n     */\n" : '';
        $storeDoc   = $docs ? "    /**\n     * @param array \$data\n     * @return {$model}\n     */\n" : '';
        $updateDoc  = $docs ? "    /**\n     * @param {$model} \${$var}\n     * @param array \$data\n     * @return {$model}\n     */\n" : '';
        $deleteDoc  = $docs ? "    /**\n     * @param {$model} \${$var}\n     * @return void\n     */\n" : '';

        return <<<PHP
{$indexDoc}    public function index(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return \$this->repository->paginate();
    }

{$showDoc}    public function show({$model} \$model): {$model}
    {
        return \$model->load([{$loadArray}]);
    }

{$storeDoc}    public function store(array \$data): {$model}
    {
        return \$this->repository->create(\$data);
    }

{$updateDoc}    public function update({$model} \$model, array \$data): {$model}
    {
        return \$this->repository->update(\$model, \$data);
    }

{$deleteDoc}    public function delete({$model} \$model): void
    {
        \$this->repository->delete(\$model);
    }
PHP;
    }

    private function renderInterface(string $model): string
    {
        $docs = config('crud-generator.generate_php_docs', false);
        $var  = lcfirst($model);

        $indexDoc   = $docs ? "    /**\n     * @return \\Illuminate\\Contracts\\Pagination\\LengthAwarePaginator\n     */\n" : '';
        $showDoc    = $docs ? "    /**\n     * @param {$model} \${$var}\n     * @return {$model}\n     */\n" : '';
        $storeDoc   = $docs ? "    /**\n     * @param array \$data\n     * @return {$model}\n     */\n" : '';
        $updateDoc  = $docs ? "    /**\n     * @param {$model} \${$var}\n     * @param array \$data\n     * @return {$model}\n     */\n" : '';
        $deleteDoc  = $docs ? "    /**\n     * @param {$model} \${$var}\n     * @return void\n     */\n" : '';

        return <<<PHP
{$indexDoc}    public function index(): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

{$showDoc}    public function show({$model} \$model): {$model};

{$storeDoc}    public function store(array \$data): {$model};

{$updateDoc}    public function update({$model} \$model, array \$data): {$model};

{$deleteDoc}    public function delete({$model} \$model): void;
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