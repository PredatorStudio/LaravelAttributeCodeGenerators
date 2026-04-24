<?php

namespace Tests\Unit\Builders;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\User;
use Vendor\LaravelAttributeCodeGenerators\Generators\Builders\DtoBuilder;

class DtoBuilderTest extends TestCase
{
    private DtoBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new DtoBuilder();
    }

    public function test_generates_class_with_correct_name(): void
    {
        $output = $this->builder->build(User::class);

        $this->assertStringContainsString('class UserDTO', $output);
    }

    public function test_generates_correct_namespace(): void
    {
        $output = $this->builder->build(User::class);

        $this->assertStringContainsString('namespace App\DTO;', $output);
    }

    public function test_generates_id_property(): void
    {
        $output = $this->builder->build(User::class);

        $this->assertStringContainsString('public readonly ?int $id = null', $output);
    }

    public function test_generates_string_property_from_fields(): void
    {
        $output = $this->builder->build(User::class);

        $this->assertStringContainsString('public readonly ?string $name = null', $output);
        $this->assertStringContainsString('public readonly ?string $email = null', $output);
    }

    public function test_generates_from_array_method(): void
    {
        $output = $this->builder->build(User::class);

        $this->assertStringContainsString('public static function fromArray(array $data): self', $output);
        $this->assertStringContainsString("name: \$data['name'] ?? null", $output);
    }

    public function test_generates_to_array_method(): void
    {
        $output = $this->builder->build(User::class);

        $this->assertStringContainsString('public function toArray(): array', $output);
        $this->assertStringContainsString("'name' => \$this->name", $output);
    }

    public function test_skips_id_and_timestamps_in_fields(): void
    {
        $output = $this->builder->build(User::class);

        $this->assertStringNotContainsString('created_at', $output);
        $this->assertStringNotContainsString('updated_at', $output);
        // id is handled separately as the first constructor arg
        $this->assertSame(1, substr_count($output, '$id'));
    }

    public function test_skips_hidden_fields(): void
    {
        $output = $this->builder->build(Post::class);

        $this->assertStringContainsString('$title', $output);
        $this->assertStringContainsString('$body', $output);
        $this->assertStringNotContainsString('$secret_hash', $output);
    }
}
