<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Vendor\LaravelAttributeCodeGenerators\Generators\RuleGenerator;

class RuleGeneratorTest extends TestCase
{
    private RuleGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new RuleGenerator();
    }

    public function test_required_for_non_nullable_column(): void
    {
        $rules = $this->generator->generate(
            [['name' => 'name', 'type' => 'string', 'nullable' => false, 'unique' => false, 'foreign' => false]],
            'users'
        );

        $this->assertContains('required', $rules['name']);
    }

    public function test_nullable_for_nullable_column(): void
    {
        $rules = $this->generator->generate(
            [['name' => 'bio', 'type' => 'text', 'nullable' => true, 'unique' => false, 'foreign' => false]],
            'users'
        );

        $this->assertContains('nullable', $rules['bio']);
        $this->assertNotContains('required', $rules['bio']);
    }

    public function test_string_rule_for_string_type(): void
    {
        $rules = $this->generator->generate(
            [['name' => 'name', 'type' => 'string', 'nullable' => false, 'unique' => false, 'foreign' => false]],
            'users'
        );

        $this->assertContains('string', $rules['name']);
    }

    public function test_integer_rule_for_integer_type(): void
    {
        $rules = $this->generator->generate(
            [['name' => 'age', 'type' => 'integer', 'nullable' => false, 'unique' => false, 'foreign' => false]],
            'users'
        );

        $this->assertContains('integer', $rules['age']);
    }

    public function test_email_rule_added_when_column_name_contains_email(): void
    {
        $rules = $this->generator->generate(
            [['name' => 'email', 'type' => 'string', 'nullable' => false, 'unique' => false, 'foreign' => false]],
            'users'
        );

        $this->assertContains('email', $rules['email']);
    }

    public function test_exists_rule_for_foreign_key(): void
    {
        $rules = $this->generator->generate(
            [['name' => 'category_id', 'type' => 'foreignId', 'nullable' => false, 'unique' => false, 'foreign' => true]],
            'posts'
        );

        $existsRule = collect($rules['category_id'])->first(fn($r) => str_starts_with($r, 'exists:'));
        $this->assertSame('exists:categories,id', $existsRule);
    }

    public function test_ignores_id_and_timestamp_columns(): void
    {
        $columns = [
            ['name' => 'id', 'type' => 'id', 'nullable' => false, 'unique' => false, 'foreign' => false],
            ['name' => 'created_at', 'type' => 'timestamp', 'nullable' => true, 'unique' => false, 'foreign' => false],
            ['name' => 'updated_at', 'type' => 'timestamp', 'nullable' => true, 'unique' => false, 'foreign' => false],
            ['name' => 'title', 'type' => 'string', 'nullable' => false, 'unique' => false, 'foreign' => false],
        ];

        $rules = $this->generator->generate($columns, 'posts');

        $this->assertArrayNotHasKey('id', $rules);
        $this->assertArrayNotHasKey('created_at', $rules);
        $this->assertArrayNotHasKey('updated_at', $rules);
        $this->assertArrayHasKey('title', $rules);
    }

    public function test_guesses_plural_table_name_for_foreign_key(): void
    {
        $rules = $this->generator->generate(
            [['name' => 'user_id', 'type' => 'foreignId', 'nullable' => false, 'unique' => false, 'foreign' => true]],
            'posts'
        );

        $this->assertContains('exists:users,id', $rules['user_id']);
    }

    public function test_partial_update_prepends_sometimes_to_all_rules(): void
    {
        $columns = [
            ['name' => 'title', 'type' => 'string',  'nullable' => false, 'unique' => false, 'foreign' => false],
            ['name' => 'bio',   'type' => 'text',     'nullable' => true,  'unique' => false, 'foreign' => false],
        ];

        $rules = $this->generator->generate($columns, 'posts', [], true);

        $this->assertSame('sometimes', $rules['title'][0]);
        $this->assertContains('required', $rules['title']);
        $this->assertSame('sometimes', $rules['bio'][0]);
        $this->assertContains('nullable', $rules['bio']);
    }

    public function test_store_request_does_not_have_sometimes(): void
    {
        $columns = [
            ['name' => 'title', 'type' => 'string', 'nullable' => false, 'unique' => false, 'foreign' => false],
        ];

        $rules = $this->generator->generate($columns, 'posts');

        $this->assertNotContains('sometimes', $rules['title']);
        $this->assertContains('required', $rules['title']);
    }
}
