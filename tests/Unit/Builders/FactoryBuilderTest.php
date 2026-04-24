<?php

namespace Tests\Unit\Builders;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Models\User;
use Vendor\LaravelAttributeCodeGenerators\Generators\Builders\FactoryBuilder;

class FactoryBuilderTest extends TestCase
{
    private FactoryBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new FactoryBuilder();
    }

    public function test_generates_factory_class(): void
    {
        $output = $this->builder->build(User::class);

        $this->assertStringContainsString('class UserFactory extends Factory', $output);
        $this->assertStringContainsString('public function definition(): array', $output);
    }

    public function test_uses_safe_email_for_email_field(): void
    {
        $output = $this->builder->build(User::class);

        $this->assertStringContainsString('fake()->safeEmail()', $output);
    }

    public function test_uses_name_faker_for_name_field(): void
    {
        $output = $this->builder->build(User::class);

        $this->assertStringContainsString('fake()->name()', $output);
    }

    public function test_foreign_id_uses_random_number(): void
    {
        $model = new class {
            public function fields(): array
            {
                return [
                    ['name' => 'category_id', 'type' => 'foreignId'],
                    ['name' => 'title',        'type' => 'string'],
                ];
            }
        };

        $output = $this->builder->build($model::class);

        $lines = array_filter(explode("\n", $output), fn($l) => str_contains($l, 'category_id'));
        $this->assertNotEmpty($lines);
        $line = reset($lines);
        $this->assertStringContainsString('fake()->randomNumber()', $line);
        $this->assertStringNotContainsString('fake()->word()', $line);
    }

    public function test_skips_id_field(): void
    {
        $output = $this->builder->build(User::class);

        $lines = array_filter(explode("\n", $output), fn($l) => str_contains($l, "'id'"));
        $this->assertEmpty($lines, 'id field should not appear in factory definition()');
    }
}
