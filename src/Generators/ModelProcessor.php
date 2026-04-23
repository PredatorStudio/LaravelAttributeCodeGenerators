<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators;

use Vendor\LaravelAttributeCodeGenerators\Attributes\BackedEnum;
use Vendor\LaravelAttributeCodeGenerators\Generators\Builders\ActionBuilder;
use Vendor\LaravelAttributeCodeGenerators\Generators\Builders\ControllerMethodBuilder;
use Vendor\LaravelAttributeCodeGenerators\Generators\Builders\DtoBuilder;
use Vendor\LaravelAttributeCodeGenerators\Generators\Builders\FactoryBuilder;
use Vendor\LaravelAttributeCodeGenerators\Generators\Builders\ResourceBuilder;
use Vendor\LaravelAttributeCodeGenerators\Generators\Builders\ServiceMethodBuilder;

class ModelProcessor
{
    public function __construct(
        private AttributeReader $reader,
        private StubRenderer $renderer,
        private FileWriter $writer,
        private MigrationParser $migrationParser,
        private RuleGenerator $ruleGenerator,
        private MigrationGenerator $migrationGenerator,
        private EnumGenerator $enumGenerator,
        private CrudLogger $logger,
        private ModelModifier $modifier,
    ) {}

    public function plan(string $modelClass): array
    {
        $data = $this->reader->read($modelClass);

        if (!$data['crud']) {
            return [];
        }

        $model = $data['shortName'];
        $planned = ["{$model}Controller", "{$model}Resource"];

        if ($data['service'])                              $planned[] = "{$model}Service";
        if ($data['service']?->interface)                 $planned[] = "{$model}ServiceInterface";
        if ($data['repository'])                          $planned[] = "{$model}Repository";
        if ($data['repository']?->interface)              $planned[] = "{$model}RepositoryInterface";
        if ($data['policy'])                $planned[] = "{$model}Policy";
        if ($data['validateFromMigration']) {
            $planned[] = "{$model}StoreRequest";
            $planned[] = "{$model}UpdateRequest";
        }
        if ($data['dto'])                   $planned[] = "{$model}DTO";
        if ($data['generateMigration'])     $planned[] = 'migration';
        if ($data['observer'])              $planned[] = "{$model}Observer";
        if ($data['action']) {
            $planned[] = "Create{$model}Action";
            $planned[] = "Update{$model}Action";
            $planned[] = "Delete{$model}Action";
        }
        if ($data['factory'])               $planned[] = "{$model}Factory";
        foreach ($data['backedEnums'] as $enumAttr) {
            $planned[] = $model . ucfirst($enumAttr->field);
        }
        if ($data['generateTest'])          $planned[] = "{$model}Test";

        if (method_exists($modelClass, 'fields') || $data['softDeletes'] || !empty($data['backedEnums'])) {
            $planned[] = "{$model}.php (model sync)";
        }

        return $planned;
    }

    public function process(string $modelClass, RouteCollector $routes, BindingsCollector $bindings, GenerationManifest $manifest): array
    {
        $data = $this->reader->read($modelClass);

        if (!$data['crud']) {
            $this->logger->warn("  Skipped — no #[Crud] attribute");
            return [];
        }

        $model = $data['shortName'];
        $generated = [];

        $skip = fn(string $artifact) => $manifest->isAlreadyGenerated($model, $artifact);

        $route = $this->resolveRoute($data, $model);

        if (!$skip("{$model}Controller") && $this->generateController($model, $data)) {
            $this->logger->line("  → {$model}Controller.php");
            $generated[] = "{$model}Controller";
        }

        if (!$skip("{$model}Resource") && $this->generateResource($model, $data)) {
            $this->logger->line("  → {$model}Resource.php");
            $generated[] = "{$model}Resource";
        }

        if ($data['service'] && !$skip("{$model}Service") && $this->generateService($model, $modelClass, $data)) {
            $this->logger->line("  → {$model}Service.php");
            $generated[] = "{$model}Service";
        }

        if ($data['service']?->interface && !$skip("{$model}ServiceInterface") && $this->generateServiceInterface($model, $modelClass)) {
            $this->logger->line("  → {$model}ServiceInterface.php");
            $generated[] = "{$model}ServiceInterface";
            $bindings->add("App\\Contracts\\{$model}ServiceInterface", "App\\Services\\{$model}Service");
        }

        if ($data['repository'] && !$skip("{$model}Repository") && $this->generateRepository($model, $data)) {
            $this->logger->line("  → {$model}Repository.php");
            $generated[] = "{$model}Repository";
        }

        if ($data['repository']?->interface && !$skip("{$model}RepositoryInterface") && $this->generateRepositoryInterface($model, $data)) {
            $this->logger->line("  → {$model}RepositoryInterface.php");
            $generated[] = "{$model}RepositoryInterface";
            $bindings->add("App\\Contracts\\{$model}RepositoryInterface", "App\\Repositories\\{$model}Repository");
        }

        if ($data['policy'] && !$skip("{$model}Policy") && $this->generatePolicy($model, $data)) {
            $this->logger->line("  → {$model}Policy.php");
            $generated[] = "{$model}Policy";
        }

        if ($data['validateFromMigration']) {
            if (!$skip("{$model}StoreRequest") || !$skip("{$model}UpdateRequest")) {
                [$storeWritten, $updateWritten] = $this->generateRequests($model, $data, $skip);
                if ($storeWritten) {
                    $this->logger->line("  → {$model}StoreRequest.php");
                    $generated[] = "{$model}StoreRequest";
                }
                if ($updateWritten) {
                    $this->logger->line("  → {$model}UpdateRequest.php");
                    $generated[] = "{$model}UpdateRequest";
                }
            }
        }

        if ($data['dto'] && !$skip("{$model}DTO") && $this->generateDTO($model, $modelClass)) {
            $this->logger->line("  → {$model}DTO.php");
            $generated[] = "{$model}DTO";
        }

        if ($data['generateMigration'] && !$skip('migration')) {
            $this->migrationGenerator->generate($modelClass);
            $this->logger->line("  → migration file");
            $generated[] = 'migration';
        }

        if ($data['observer'] && !$skip("{$model}Observer") && $this->generateObserver($model)) {
            $this->logger->line("  → {$model}Observer.php");
            $generated[] = "{$model}Observer";
        }

        if ($data['action']) {
            [$createWritten, $updateWritten, $deleteWritten] = $this->generateActions($model, $skip);
            if ($createWritten) {
                $this->logger->line("  → Create{$model}Action.php");
                $generated[] = "Create{$model}Action";
            }
            if ($updateWritten) {
                $this->logger->line("  → Update{$model}Action.php");
                $generated[] = "Update{$model}Action";
            }
            if ($deleteWritten) {
                $this->logger->line("  → Delete{$model}Action.php");
                $generated[] = "Delete{$model}Action";
            }
        }

        if ($data['factory'] && !$skip("{$model}Factory") && $this->generateFactory($model, $modelClass)) {
            $this->logger->line("  → {$model}Factory.php");
            $generated[] = "{$model}Factory";
        }

        foreach ($data['backedEnums'] as $enumAttr) {
            $enumName = $model . ucfirst($enumAttr->field);
            if (!$skip($enumName) && $this->generateEnum($enumName, $enumAttr)) {
                $this->logger->line("  → {$enumName}.php");
                $generated[] = $enumName;
            }
        }

        if ($data['generateTest'] && !$skip("{$model}Test") && $this->generateFeatureTest($model, $route)) {
            $this->logger->line("  → {$model}Test.php");
            $generated[] = "{$model}Test";
        }

        $modelSyncKey = "{$model} (model sync)";
        if (!$skip($modelSyncKey) && $this->modifier->modify($modelClass, $data)) {
            $this->logger->line("  → {$model}.php (model synced)");
            $generated[] = $modelSyncKey;
        }

        $routes->add($route, $model . 'Controller', $data['crud']->methods ?? []);

        return $generated;
    }

    private function generateController(string $model, array $data): bool
    {
        $useInterface = $data['service']?->interface;

        return $this->writer->write(
            app_path("Http/Controllers/{$model}Controller.php"),
            $this->renderer->render(
                $this->stubPath('controller.stub'),
                [
                    'model'        => $model,
                    'service_fqn'  => $useInterface ? "App\\Contracts\\{$model}ServiceInterface" : "App\\Services\\{$model}Service",
                    'service_type' => $useInterface ? "{$model}ServiceInterface" : "{$model}Service",
                    'methods'      => (new ControllerMethodBuilder())->build($model),
                ]
            )
        );
    }

    private function generateResource(string $model, array $data): bool
    {
        return $this->writer->write(
            app_path("Http/Resources/{$model}Resource.php"),
            $this->renderer->render(
                $this->stubPath('resource.stub'),
                ['model' => $model, 'body' => (new ResourceBuilder())->build($data['resource'])]
            )
        );
    }

    private function generateService(string $model, string $modelClass, array $data): bool
    {
        $repoInterface = $data['repository']?->interface;
        $selfInterface = $data['service']?->interface;

        return $this->writer->write(
            app_path("Services/{$model}Service.php"),
            $this->renderer->render(
                $this->stubPath('service.stub'),
                [
                    'model'                  => $model,
                    'methods'                => (new ServiceMethodBuilder())->build($modelClass),
                    'repository_fqn'         => $repoInterface ? "App\\Contracts\\{$model}RepositoryInterface" : "App\\Repositories\\{$model}Repository",
                    'repository_type'        => $repoInterface ? "{$model}RepositoryInterface" : "{$model}Repository",
                    'service_interface_use'  => $selfInterface ? "\nuse App\\Contracts\\{$model}ServiceInterface;" : '',
                    'implements'             => $selfInterface ? " implements {$model}ServiceInterface" : '',
                ]
            )
        );
    }

    private function generateRepository(string $model, array $data): bool
    {
        $selfInterface = $data['repository']?->interface;

        return $this->writer->write(
            app_path("Repositories/{$model}Repository.php"),
            $this->renderer->render(
                $this->stubPath('repository.stub'),
                [
                    'model'                       => $model,
                    'soft_deletes'                => $data['softDeletes'] ? $this->softDeleteMethods($model) : '',
                    'repository_interface_use'    => $selfInterface ? "\nuse App\\Contracts\\{$model}RepositoryInterface;" : '',
                    'implements'                  => $selfInterface ? " implements {$model}RepositoryInterface" : '',
                ]
            )
        );
    }

    private function softDeleteMethods(string $model): string
    {
        return <<<PHP

    public function restore({$model} \$model): void
    {
        \$model->restore();
    }

    public function forceDelete({$model} \$model): void
    {
        \$model->forceDelete();
    }

PHP;
    }

    private function generateServiceInterface(string $model, string $modelClass): bool
    {
        return $this->writer->write(
            app_path("Contracts/{$model}ServiceInterface.php"),
            $this->renderer->render(
                $this->stubPath('contract-service.stub'),
                [
                    'model'   => $model,
                    'methods' => (new ServiceMethodBuilder())->buildInterface($modelClass),
                ]
            )
        );
    }

    private function generateRepositoryInterface(string $model, array $data): bool
    {
        return $this->writer->write(
            app_path("Contracts/{$model}RepositoryInterface.php"),
            $this->renderer->render(
                $this->stubPath('contract-repository.stub'),
                [
                    'model'        => $model,
                    'soft_deletes' => $data['softDeletes'] ? $this->softDeleteInterfaceMethods($model) : '',
                ]
            )
        );
    }

    private function softDeleteInterfaceMethods(string $model): string
    {
        return <<<PHP

    public function restore({$model} \$model): void;

    public function forceDelete({$model} \$model): void;

PHP;
    }

    private function generatePolicy(string $model, array $data): bool
    {
        return $this->writer->write(
            app_path("Policies/{$model}Policy.php"),
            $this->renderer->render($this->stubPath('policy.stub'), ['model' => $model])
        );
    }

    private function generateRequests(string $model, array $data, callable $skip): array
    {
        $columns = $this->migrationParser->parse(strtolower($model));
        $rules = $this->ruleGenerator->generate($columns, strtolower($model));

        return [
            !$skip("{$model}StoreRequest") ? $this->writeRequest($model, 'store', $rules) : false,
            !$skip("{$model}UpdateRequest") ? $this->writeRequest($model, 'update', $rules) : false,
        ];
    }

    private function writeRequest(string $model, string $type, array $rules): bool
    {
        return $this->writer->write(
            app_path("Http/Requests/{$model}" . ucfirst($type) . "Request.php"),
            $this->renderer->render(
                $this->stubPath("{$type}-request.stub"),
                ['model' => $model, 'rules' => $this->formatRules($rules)]
            )
        );
    }

    private function formatRules(array $rules): string
    {
        $output = '';

        foreach ($rules as $field => $ruleArray) {
            $ruleString = implode("','", $ruleArray);
            $output .= "            '{$field}' => ['{$ruleString}'],\n";
        }

        return rtrim($output);
    }

    private function generateDTO(string $model, string $modelClass): bool
    {
        return $this->writer->write(
            app_path("DTO/{$model}DTO.php"),
            (new DtoBuilder())->build($modelClass)
        );
    }

    private function generateObserver(string $model): bool
    {
        return $this->writer->write(
            app_path("Observers/{$model}Observer.php"),
            $this->renderer->render(
                $this->stubPath('observer.stub'),
                ['model' => $model, 'var' => lcfirst($model)]
            )
        );
    }

    private function generateActions(string $model, callable $skip): array
    {
        $builder = new ActionBuilder();

        return [
            !$skip("Create{$model}Action") ? $this->writer->write(app_path("Actions/Create{$model}Action.php"), $builder->build($model)) : false,
            !$skip("Update{$model}Action") ? $this->writer->write(app_path("Actions/Update{$model}Action.php"), $builder->buildUpdate($model)) : false,
            !$skip("Delete{$model}Action") ? $this->writer->write(app_path("Actions/Delete{$model}Action.php"), $builder->buildDelete($model)) : false,
        ];
    }

    private function generateFactory(string $model, string $modelClass): bool
    {
        return $this->writer->write(
            database_path("factories/{$model}Factory.php"),
            (new FactoryBuilder())->build($modelClass)
        );
    }

    private function generateEnum(string $enumName, BackedEnum $attr): bool
    {
        return $this->writer->write(
            app_path("Enums/{$enumName}.php"),
            $this->enumGenerator->generate($enumName, $attr)
        );
    }

    private function generateFeatureTest(string $model, string $route): bool
    {
        return $this->writer->write(
            base_path("tests/Feature/{$model}Test.php"),
            $this->renderer->render(
                $this->stubPath('feature-test.stub'),
                ['model' => $model, 'var' => lcfirst($model), 'route' => $route]
            )
        );
    }

    private function resolveRoute(array $data, string $model): string
    {
        return $data['route']?->path ?? strtolower($model) . 's';
    }

    private function stubPath(string $name): string
    {
        return __DIR__ . '/../stubs/' . $name;
    }
}
