<?php

namespace Tests\Feature;

use Mockery;
use Tests\Fixtures\Models\User;
use Tests\Support\MemoryFileWriter;
use Tests\Support\MemoryGenerationManifest;
use Tests\Support\MemoryModelModifier;
use Tests\Support\MemoryRouteCollector;
use Tests\TestCase;
use Vendor\LaravelAttributeCodeGenerators\Generators\CrudGenerator;
use Vendor\LaravelAttributeCodeGenerators\Generators\FileWriter;
use Vendor\LaravelAttributeCodeGenerators\Generators\GenerationManifest;
use Vendor\LaravelAttributeCodeGenerators\Generators\MigrationParser;
use Vendor\LaravelAttributeCodeGenerators\Generators\ModelModifier;
use Vendor\LaravelAttributeCodeGenerators\Generators\ModelScanner;
use Vendor\LaravelAttributeCodeGenerators\Generators\RouteCollector;

class CrudGeneratorTest extends TestCase
{
    private MemoryFileWriter $fileWriter;
    private MemoryRouteCollector $routeCollector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileWriter = new MemoryFileWriter();
        $this->routeCollector = new MemoryRouteCollector();

        $this->app->instance(FileWriter::class, $this->fileWriter);
        $this->app->instance(RouteCollector::class, $this->routeCollector);
        $this->app->instance(ModelModifier::class, new MemoryModelModifier());
        $this->app->instance(GenerationManifest::class, new MemoryGenerationManifest());

        $scanner = Mockery::mock(ModelScanner::class);
        $scanner->shouldReceive('scan')->andReturn([User::class]);
        $this->app->instance(ModelScanner::class, $scanner);

        $parser = Mockery::mock(MigrationParser::class);
        $parser->shouldReceive('parse')->with('user')->andReturn([
            ['name' => 'name',  'type' => 'string', 'nullable' => false, 'unique' => false, 'foreign' => false],
            ['name' => 'email', 'type' => 'string', 'nullable' => false, 'unique' => true,  'foreign' => false],
            ['name' => 'bio',   'type' => 'text',   'nullable' => true,  'unique' => false, 'foreign' => false],
        ]);
        $this->app->instance(MigrationParser::class, $parser);

        // NullCrudLogger is already bound by the provider — no need to override
        $this->app->make(CrudGenerator::class)->generateAll();
    }

    // --- Controller ---

    public function test_generates_controller_file(): void
    {
        $this->assertTrue($this->fileWriter->has('Http/Controllers/UserController.php'));
    }

    public function test_controller_contains_correct_class_declaration(): void
    {
        $content = $this->fileWriter->get('Http/Controllers/UserController.php');

        $this->assertStringContainsString('class UserController extends Controller', $content);
    }

    public function test_controller_injects_service(): void
    {
        $content = $this->fileWriter->get('Http/Controllers/UserController.php');

        $this->assertStringContainsString('private UserService $service', $content);
    }

    public function test_controller_contains_all_crud_methods(): void
    {
        $content = $this->fileWriter->get('Http/Controllers/UserController.php');

        foreach (['index', 'show', 'store', 'update', 'destroy'] as $method) {
            $this->assertStringContainsString("public function {$method}(", $content, "Missing method: {$method}");
        }
    }

    // --- Resource ---

    public function test_generates_resource_file(): void
    {
        $this->assertTrue($this->fileWriter->has('Http/Resources/UserResource.php'));
    }

    public function test_resource_extends_json_resource(): void
    {
        $content = $this->fileWriter->get('Http/Resources/UserResource.php');

        $this->assertStringContainsString('class UserResource extends JsonResource', $content);
    }

    public function test_resource_contains_specified_fields(): void
    {
        $content = $this->fileWriter->get('Http/Resources/UserResource.php');

        $this->assertStringContainsString("'id'", $content);
        $this->assertStringContainsString("'name'", $content);
        $this->assertStringContainsString("'email'", $content);
    }

    // --- Service ---

    public function test_generates_service_file(): void
    {
        $this->assertTrue($this->fileWriter->has('Services/UserService.php'));
    }

    public function test_service_contains_correct_class_declaration(): void
    {
        $content = $this->fileWriter->get('Services/UserService.php');

        $this->assertStringContainsString('class UserService', $content);
    }

    public function test_service_injects_repository(): void
    {
        $content = $this->fileWriter->get('Services/UserService.php');

        $this->assertStringContainsString('UserRepository $repository', $content);
    }

    // --- Repository ---

    public function test_generates_repository_file(): void
    {
        $this->assertTrue($this->fileWriter->has('Repositories/UserRepository.php'));
    }

    public function test_repository_contains_crud_methods(): void
    {
        $content = $this->fileWriter->get('Repositories/UserRepository.php');

        $this->assertStringContainsString('public function paginate()', $content);
        $this->assertStringContainsString('public function create(array $data)', $content);
        $this->assertStringContainsString('public function update(User $model, array $data)', $content);
        $this->assertStringContainsString('public function delete(User $model)', $content);
    }

    public function test_repository_contains_soft_delete_methods(): void
    {
        $content = $this->fileWriter->get('Repositories/UserRepository.php');

        $this->assertStringContainsString('public function restore(User $model)', $content);
        $this->assertStringContainsString('public function forceDelete(User $model)', $content);
    }

    // --- Policy ---

    public function test_generates_policy_file(): void
    {
        $this->assertTrue($this->fileWriter->has('Policies/UserPolicy.php'));
    }

    public function test_policy_contains_gate_methods(): void
    {
        $content = $this->fileWriter->get('Policies/UserPolicy.php');

        foreach (['viewAny', 'view', 'create', 'update', 'delete'] as $method) {
            $this->assertStringContainsString("public function {$method}(", $content);
        }
    }

    // --- Form Requests ---

    public function test_generates_store_request_file(): void
    {
        $this->assertTrue($this->fileWriter->has('Http/Requests/UserStoreRequest.php'));
    }

    public function test_generates_update_request_file(): void
    {
        $this->assertTrue($this->fileWriter->has('Http/Requests/UserUpdateRequest.php'));
    }

    public function test_store_request_contains_validation_rules(): void
    {
        $content = $this->fileWriter->get('Http/Requests/UserStoreRequest.php');

        $this->assertStringContainsString("'name'", $content);
        $this->assertStringContainsString("'email'", $content);
        $this->assertStringContainsString("'required'", $content);
    }

    public function test_update_request_has_nullable_bio(): void
    {
        $content = $this->fileWriter->get('Http/Requests/UserUpdateRequest.php');

        $this->assertStringContainsString("'bio'", $content);
        $this->assertStringContainsString("'nullable'", $content);
    }

    // --- DTO ---

    public function test_generates_dto_file(): void
    {
        $this->assertTrue($this->fileWriter->has('DTO/UserDTO.php'));
    }

    public function test_dto_contains_model_fields(): void
    {
        $content = $this->fileWriter->get('DTO/UserDTO.php');

        $this->assertStringContainsString('$name', $content);
        $this->assertStringContainsString('$email', $content);
    }

    // --- Observer ---

    public function test_generates_observer_file(): void
    {
        $this->assertTrue($this->fileWriter->has('Observers/UserObserver.php'));
    }

    public function test_observer_contains_lifecycle_methods(): void
    {
        $content = $this->fileWriter->get('Observers/UserObserver.php');

        foreach (['creating', 'created', 'updating', 'updated', 'deleting', 'deleted'] as $method) {
            $this->assertStringContainsString("public function {$method}(", $content, "Missing observer method: {$method}");
        }
    }

    public function test_observer_has_correct_namespace(): void
    {
        $content = $this->fileWriter->get('Observers/UserObserver.php');

        $this->assertStringContainsString('namespace App\Observers', $content);
        $this->assertStringContainsString('class UserObserver', $content);
    }

    // --- Actions ---

    public function test_generates_create_action_file(): void
    {
        $this->assertTrue($this->fileWriter->has('Actions/CreateUserAction.php'));
    }

    public function test_generates_update_action_file(): void
    {
        $this->assertTrue($this->fileWriter->has('Actions/UpdateUserAction.php'));
    }

    public function test_generates_delete_action_file(): void
    {
        $this->assertTrue($this->fileWriter->has('Actions/DeleteUserAction.php'));
    }

    public function test_create_action_has_execute_method(): void
    {
        $content = $this->fileWriter->get('Actions/CreateUserAction.php');

        $this->assertStringContainsString('class CreateUserAction', $content);
        $this->assertStringContainsString('public function execute(array $data): User', $content);
    }

    public function test_delete_action_has_void_return(): void
    {
        $content = $this->fileWriter->get('Actions/DeleteUserAction.php');

        $this->assertStringContainsString('public function execute(User $user): void', $content);
    }

    // --- Factory ---

    public function test_generates_factory_file(): void
    {
        $this->assertTrue($this->fileWriter->has('factories/UserFactory.php'));
    }

    public function test_factory_extends_factory_class(): void
    {
        $content = $this->fileWriter->get('factories/UserFactory.php');

        $this->assertStringContainsString('class UserFactory extends Factory', $content);
        $this->assertStringContainsString('public function definition(): array', $content);
    }

    public function test_factory_uses_faker_for_fields(): void
    {
        $content = $this->fileWriter->get('factories/UserFactory.php');

        $this->assertStringContainsString("'name'", $content);
        $this->assertStringContainsString("'email'", $content);
        $this->assertStringContainsString('fake()', $content);
    }

    // --- BackedEnum ---

    public function test_generates_enum_file(): void
    {
        $this->assertTrue($this->fileWriter->has('Enums/UserStatus.php'));
    }

    public function test_enum_has_correct_cases(): void
    {
        $content = $this->fileWriter->get('Enums/UserStatus.php');

        $this->assertStringContainsString('enum UserStatus: string', $content);
        $this->assertStringContainsString("case Active = 'active'", $content);
        $this->assertStringContainsString("case Inactive = 'inactive'", $content);
    }

    // --- GenerateTest ---

    public function test_generates_feature_test_file(): void
    {
        $this->assertTrue($this->fileWriter->has('tests/Feature/UserTest.php'));
    }

    public function test_feature_test_contains_crud_test_methods(): void
    {
        $content = $this->fileWriter->get('tests/Feature/UserTest.php');

        $this->assertStringContainsString('class UserTest', $content);
        $this->assertStringContainsString('test_can_list_users', $content);
        $this->assertStringContainsString('test_can_create_user', $content);
        $this->assertStringContainsString('test_can_show_user', $content);
        $this->assertStringContainsString('test_can_update_user', $content);
        $this->assertStringContainsString('test_can_delete_user', $content);
    }

    public function test_feature_test_uses_correct_route(): void
    {
        $content = $this->fileWriter->get('tests/Feature/UserTest.php');

        $this->assertStringContainsString('/api/users', $content);
    }

    // --- Routes ---

    public function test_generates_route_for_model(): void
    {
        $this->assertStringContainsString(
            "Route::apiResource('users', UserController::class)",
            $this->routeCollector->output
        );
    }

    public function test_route_limits_methods_from_crud_attribute(): void
    {
        $this->assertStringContainsString(
            "->only(['index','store','show','update','destroy'])",
            $this->routeCollector->output
        );
    }
}
