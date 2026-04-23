<?php

namespace Tests\Unit;

use Tests\Fixtures\Models\User;
use Tests\TestCase;
use Vendor\LaravelAttributeCodeGenerators\Generators\MigrationGenerator;
use Vendor\LaravelAttributeCodeGenerators\Generators\MigrationParser;

class MigrationParserTest extends TestCase
{
    private MigrationParser $parser;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/crud_migration_test_' . uniqid();
        mkdir($this->tempDir . '/migrations', 0755, true);

        // Point database_path() to temp dir before generating
        $this->app->useDatabasePath($this->tempDir);

        // Generate migration from User::fields() — same as production flow
        (new MigrationGenerator())->generate(User::class);

        $this->parser = new MigrationParser();
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempDir);

        parent::tearDown();
    }

    public function test_parses_string_column(): void
    {
        $column = collect($this->parser->parse('users'))->firstWhere('name', 'name');

        $this->assertNotNull($column, 'Column "name" should be parsed');
        $this->assertSame('string', $column['type']);
        $this->assertFalse($column['nullable']);
    }

    public function test_parses_nullable_column(): void
    {
        $column = collect($this->parser->parse('users'))->firstWhere('name', 'bio');

        $this->assertNotNull($column, 'Column "bio" should be parsed');
        $this->assertTrue($column['nullable']);
    }

    public function test_parses_unique_modifier(): void
    {
        $column = collect($this->parser->parse('users'))->firstWhere('name', 'email');

        $this->assertNotNull($column, 'Column "email" should be parsed');
        $this->assertTrue($column['unique']);
    }

    public function test_returns_empty_array_for_unknown_table(): void
    {
        $this->assertSame([], $this->parser->parse('nonexistent_table'));
    }

    private function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        foreach (scandir($path) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $full = $path . '/' . $entry;
            is_dir($full) ? $this->deleteDirectory($full) : unlink($full);
        }

        rmdir($path);
    }
}
