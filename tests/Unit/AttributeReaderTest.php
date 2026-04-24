<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Models\User;
use Vendor\LaravelAttributeCodeGenerators\Attributes\BackedEnum;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Crud;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Repository;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Resource;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Route;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Seeder;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Service;
use Vendor\LaravelAttributeCodeGenerators\Generators\AttributeReader;

class AttributeReaderTest extends TestCase
{
    private AttributeReader $reader;

    protected function setUp(): void
    {
        $this->reader = new AttributeReader();
    }

    public function test_reads_short_name(): void
    {
        $data = $this->reader->read(User::class);

        $this->assertSame('User', $data['shortName']);
    }

    public function test_reads_crud_attribute_with_methods(): void
    {
        $data = $this->reader->read(User::class);

        $this->assertInstanceOf(Crud::class, $data['crud']);
        $this->assertSame(['index', 'store', 'show', 'update', 'destroy'], $data['crud']->methods);
    }

    public function test_reads_route_attribute_with_path(): void
    {
        $data = $this->reader->read(User::class);

        $this->assertInstanceOf(Route::class, $data['route']);
        $this->assertSame('users', $data['route']->path);
    }

    public function test_detects_service_attribute(): void
    {
        $data = $this->reader->read(User::class);

        $this->assertInstanceOf(Service::class, $data['service']);
    }

    public function test_detects_repository_attribute(): void
    {
        $data = $this->reader->read(User::class);

        $this->assertInstanceOf(Repository::class, $data['repository']);
    }

    public function test_detects_policy_attribute(): void
    {
        $data = $this->reader->read(User::class);

        $this->assertTrue($data['policy']);
    }

    public function test_detects_validate_from_migration_attribute(): void
    {
        $data = $this->reader->read(User::class);

        $this->assertTrue($data['validateFromMigration']);
    }

    public function test_detects_dto_attribute(): void
    {
        $data = $this->reader->read(User::class);

        $this->assertTrue($data['dto']);
    }

    public function test_detects_resource_attribute_with_fields(): void
    {
        $data = $this->reader->read(User::class);

        $this->assertInstanceOf(Resource::class, $data['resource']);
        $this->assertSame(['id', 'name', 'email'], $data['resource']->fields);
    }

    public function test_detects_soft_deletes_attribute(): void
    {
        $data = $this->reader->read(User::class);

        $this->assertTrue($data['softDeletes']);
    }

    public function test_reads_backed_enum_instances(): void
    {
        $data = $this->reader->read(User::class);

        $this->assertIsArray($data['backedEnums']);
        $this->assertCount(1, $data['backedEnums']);
        $this->assertInstanceOf(BackedEnum::class, $data['backedEnums'][0]);
        $this->assertSame('status', $data['backedEnums'][0]->field);
        $this->assertSame(['active', 'inactive'], $data['backedEnums'][0]->values);
        $this->assertSame('string', $data['backedEnums'][0]->type);
    }

    public function test_detects_observer_attribute(): void
    {
        $data = $this->reader->read(User::class);

        $this->assertTrue($data['observer']);
    }

    public function test_detects_action_attribute(): void
    {
        $data = $this->reader->read(User::class);

        $this->assertTrue($data['action']);
    }

    public function test_detects_factory_attribute(): void
    {
        $data = $this->reader->read(User::class);

        $this->assertTrue($data['factory']);
    }

    public function test_detects_seeder_attribute_with_count(): void
    {
        $data = $this->reader->read(User::class);

        $this->assertInstanceOf(Seeder::class, $data['seeder']);
        $this->assertSame(5, $data['seeder']->count);
    }

    public function test_detects_generate_test_attribute(): void
    {
        $data = $this->reader->read(User::class);

        $this->assertTrue($data['generateTest']);
    }

    public function test_returns_false_for_missing_attributes_on_plain_class(): void
    {
        $plain = new class {};
        $data = $this->reader->read($plain::class);

        $this->assertNull($data['crud']);
        $this->assertNull($data['route']);
        $this->assertNull($data['resource']);
        $this->assertNull($data['service']);
        $this->assertNull($data['repository']);
        $this->assertFalse($data['policy']);
        $this->assertFalse($data['softDeletes']);
        $this->assertFalse($data['observer']);
        $this->assertFalse($data['action']);
        $this->assertFalse($data['factory']);
        $this->assertNull($data['seeder']);
        $this->assertFalse($data['generateTest']);
        $this->assertSame([], $data['backedEnums']);
    }
}
