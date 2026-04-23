<?php

namespace Tests\Feature;

use Mockery;
use Tests\Fixtures\Models\Product;
use Tests\Support\MemoryBindingsCollector;
use Tests\Support\MemoryFileWriter;
use Tests\Support\MemoryGenerationManifest;
use Tests\Support\MemoryModelModifier;
use Tests\Support\MemoryRouteCollector;
use Tests\TestCase;
use Vendor\LaravelAttributeCodeGenerators\Generators\BindingsCollector;
use Vendor\LaravelAttributeCodeGenerators\Generators\CrudGenerator;
use Vendor\LaravelAttributeCodeGenerators\Generators\FileWriter;
use Vendor\LaravelAttributeCodeGenerators\Generators\GenerationManifest;
use Vendor\LaravelAttributeCodeGenerators\Generators\ModelModifier;
use Vendor\LaravelAttributeCodeGenerators\Generators\ModelScanner;
use Vendor\LaravelAttributeCodeGenerators\Generators\RouteCollector;

class InterfaceGenerationTest extends TestCase
{
    private MemoryFileWriter $fileWriter;
    private MemoryBindingsCollector $bindings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileWriter = new MemoryFileWriter();
        $this->bindings = new MemoryBindingsCollector();

        $this->app->instance(FileWriter::class, $this->fileWriter);
        $this->app->instance(RouteCollector::class, new MemoryRouteCollector());
        $this->app->instance(BindingsCollector::class, $this->bindings);
        $this->app->instance(ModelModifier::class, new MemoryModelModifier());
        $this->app->instance(GenerationManifest::class, new MemoryGenerationManifest());

        $scanner = Mockery::mock(ModelScanner::class);
        $scanner->shouldReceive('scan')->andReturn([Product::class]);
        $this->app->instance(ModelScanner::class, $scanner);

        $this->app->make(CrudGenerator::class)->generateAll();
    }

    // --- ServiceInterface ---

    public function test_generates_service_interface_file(): void
    {
        $this->assertTrue($this->fileWriter->has('Contracts/ProductServiceInterface.php'));
    }

    public function test_service_interface_is_a_php_interface(): void
    {
        $content = $this->fileWriter->get('Contracts/ProductServiceInterface.php');

        $this->assertStringContainsString('interface ProductServiceInterface', $content);
    }

    public function test_service_interface_has_correct_namespace(): void
    {
        $content = $this->fileWriter->get('Contracts/ProductServiceInterface.php');

        $this->assertStringContainsString('namespace App\Contracts', $content);
    }

    public function test_service_interface_declares_crud_method_signatures(): void
    {
        $content = $this->fileWriter->get('Contracts/ProductServiceInterface.php');

        $this->assertStringContainsString('public function index();', $content);
        $this->assertStringContainsString('public function store(array $data);', $content);
        $this->assertStringContainsString('public function show(Product $model);', $content);
        $this->assertStringContainsString('public function update(Product $model, array $data);', $content);
        $this->assertStringContainsString('public function delete(Product $model);', $content);
    }

    // --- RepositoryInterface ---

    public function test_generates_repository_interface_file(): void
    {
        $this->assertTrue($this->fileWriter->has('Contracts/ProductRepositoryInterface.php'));
    }

    public function test_repository_interface_is_a_php_interface(): void
    {
        $content = $this->fileWriter->get('Contracts/ProductRepositoryInterface.php');

        $this->assertStringContainsString('interface ProductRepositoryInterface', $content);
    }

    public function test_repository_interface_declares_crud_method_signatures(): void
    {
        $content = $this->fileWriter->get('Contracts/ProductRepositoryInterface.php');

        $this->assertStringContainsString('public function paginate();', $content);
        $this->assertStringContainsString('public function create(array $data): Product;', $content);
        $this->assertStringContainsString('public function update(Product $model, array $data): Product;', $content);
        $this->assertStringContainsString('public function delete(Product $model): void;', $content);
    }

    // --- Service implements interface ---

    public function test_service_implements_service_interface(): void
    {
        $content = $this->fileWriter->get('Services/ProductService.php');

        $this->assertStringContainsString('class ProductService implements ProductServiceInterface', $content);
    }

    public function test_service_imports_service_interface(): void
    {
        $content = $this->fileWriter->get('Services/ProductService.php');

        $this->assertStringContainsString('use App\Contracts\ProductServiceInterface;', $content);
    }

    public function test_service_injects_repository_interface(): void
    {
        $content = $this->fileWriter->get('Services/ProductService.php');

        $this->assertStringContainsString('private ProductRepositoryInterface $repository', $content);
        $this->assertStringContainsString('use App\Contracts\ProductRepositoryInterface;', $content);
    }

    // --- Repository implements interface ---

    public function test_repository_implements_repository_interface(): void
    {
        $content = $this->fileWriter->get('Repositories/ProductRepository.php');

        $this->assertStringContainsString('class ProductRepository implements ProductRepositoryInterface', $content);
    }

    public function test_repository_imports_repository_interface(): void
    {
        $content = $this->fileWriter->get('Repositories/ProductRepository.php');

        $this->assertStringContainsString('use App\Contracts\ProductRepositoryInterface;', $content);
    }

    // --- Controller uses interface ---

    public function test_controller_injects_service_interface(): void
    {
        $content = $this->fileWriter->get('Http/Controllers/ProductController.php');

        $this->assertStringContainsString('private ProductServiceInterface $service', $content);
        $this->assertStringContainsString('use App\Contracts\ProductServiceInterface;', $content);
    }

    // --- Bindings ---

    public function test_bindings_collector_receives_service_binding(): void
    {
        $this->assertTrue($this->bindings->has('App\Contracts\ProductServiceInterface'));
    }

    public function test_bindings_collector_receives_repository_binding(): void
    {
        $this->assertTrue($this->bindings->has('App\Contracts\ProductRepositoryInterface'));
    }
}
