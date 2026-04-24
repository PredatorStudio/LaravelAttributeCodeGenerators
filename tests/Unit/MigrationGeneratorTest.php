<?php

namespace Tests\Unit;

use Tests\Fixtures\Models\Article;
use Tests\TestCase;
use Vendor\LaravelAttributeCodeGenerators\Generators\MigrationGenerator;

class MigrationGeneratorTest extends TestCase
{
    private string $tempDir;
    private MigrationGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/crud_gen_test_' . uniqid();
        mkdir($this->tempDir . '/migrations', 0755, true);
        $this->app->useDatabasePath($this->tempDir);

        $this->generator = new MigrationGenerator();
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempDir);
        parent::tearDown();
    }

    public function test_migration_without_soft_deletes_has_only_timestamps(): void
    {
        $this->generator->generate(Article::class, false);

        $content = $this->readMigration();

        $this->assertStringContainsString('$table->timestamps()', $content);
        $this->assertStringNotContainsString('softDeletes', $content);
    }

    public function test_migration_with_soft_deletes_includes_soft_deletes_column(): void
    {
        $this->generator->generate(Article::class, true);

        $content = $this->readMigration();

        $this->assertStringContainsString('$table->timestamps()', $content);
        $this->assertStringContainsString('$table->softDeletes()', $content);
    }

    private function readMigration(): string
    {
        $files = glob($this->tempDir . '/migrations/*.php');
        $this->assertNotEmpty($files, 'No migration file was generated');
        return file_get_contents($files[0]);
    }

    private function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $full = $path . '/' . $entry;
            is_dir($full) ? $this->deleteDirectory($full) : unlink($full);
        }
        rmdir($path);
    }
}
