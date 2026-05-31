<?php

namespace Tests\Feature;

use Tests\Fixtures\Models\BlogPost;
use Tests\TestCase;
use Vendor\LaravelAttributeCodeGenerators\Generators\ApiDocsGenerator;

class ApiDocsGeneratorTest extends TestCase
{
    private string $tmpDir;
    private ApiDocsGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir() . '/crud-api-docs-test-' . uniqid();
        mkdir($this->tmpDir, 0755, true);

        config(['crud-generator.api_docs_models_path' => $this->tmpDir]);

        $this->generator = $this->app->make(ApiDocsGenerator::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        foreach (glob("{$this->tmpDir}/*.yaml") as $file) {
            unlink($file);
        }
        rmdir($this->tmpDir);
    }

    private function generateBlogPost(): string
    {
        $reader = $this->app->make(\Vendor\LaravelAttributeCodeGenerators\Generators\AttributeReader::class);
        $data   = $reader->read(BlogPost::class);
        $route  = 'blog-posts';

        $this->generator->generate('BlogPost', $data, $route);

        return file_get_contents("{$this->tmpDir}/BlogPost.yaml");
    }

    public function test_generates_yaml_file(): void
    {
        $this->generateBlogPost();

        $this->assertFileExists("{$this->tmpDir}/BlogPost.yaml");
    }

    public function test_yaml_contains_paths_section(): void
    {
        $yaml = $this->generateBlogPost();

        $this->assertStringContainsString('paths:', $yaml);
    }

    public function test_yaml_contains_collection_path(): void
    {
        $yaml = $this->generateBlogPost();

        $this->assertStringContainsString('/blog-posts:', $yaml);
    }

    public function test_yaml_contains_item_path_with_parameter(): void
    {
        $yaml = $this->generateBlogPost();

        $this->assertStringContainsString('/blog-posts/{blogPost}:', $yaml);
    }

    public function test_yaml_contains_index_operation(): void
    {
        $yaml = $this->generateBlogPost();

        $this->assertStringContainsString('operationId: BlogPost-index', $yaml);
    }

    public function test_yaml_contains_store_operation(): void
    {
        $yaml = $this->generateBlogPost();

        $this->assertStringContainsString('operationId: BlogPost-store', $yaml);
    }

    public function test_yaml_contains_show_operation(): void
    {
        $yaml = $this->generateBlogPost();

        $this->assertStringContainsString('operationId: BlogPost-show', $yaml);
    }

    public function test_yaml_contains_update_operations(): void
    {
        $yaml = $this->generateBlogPost();

        $this->assertStringContainsString('operationId: BlogPost-update', $yaml);
        $this->assertStringContainsString('operationId: BlogPost-patch', $yaml);
    }

    public function test_yaml_contains_destroy_operation(): void
    {
        $yaml = $this->generateBlogPost();

        $this->assertStringContainsString('operationId: BlogPost-destroy', $yaml);
    }

    public function test_yaml_contains_components_section(): void
    {
        $yaml = $this->generateBlogPost();

        $this->assertStringContainsString('components:', $yaml);
        $this->assertStringContainsString('schemas:', $yaml);
    }

    public function test_yaml_contains_model_schema(): void
    {
        $yaml = $this->generateBlogPost();

        $this->assertStringContainsString('BlogPost:', $yaml);
    }

    public function test_yaml_schema_contains_visible_fields(): void
    {
        $yaml = $this->generateBlogPost();

        $this->assertStringContainsString('title:', $yaml);
        $this->assertStringContainsString('body:', $yaml);
    }

    public function test_yaml_schema_excludes_hidden_fields(): void
    {
        $yaml = $this->generateBlogPost();

        // 'secret' is marked hidden in BlogPost::fields()
        $schemaSection = substr($yaml, strpos($yaml, 'components:'));
        $this->assertStringNotContainsString('secret:', $schemaSection);
    }

    public function test_yaml_contains_description_from_attribute(): void
    {
        $yaml = $this->generateBlogPost();

        $this->assertStringContainsString('description: Blog post entries', $yaml);
    }

    public function test_yaml_contains_request_schemas(): void
    {
        $yaml = $this->generateBlogPost();

        $this->assertStringContainsString('BlogPostRequest:', $yaml);
        $this->assertStringContainsString('BlogPostUpdateRequest:', $yaml);
    }

    public function test_yaml_maps_text_type_to_string(): void
    {
        $yaml = $this->generateBlogPost();

        // body is 'text' type in BlogPost::fields() — should map to OpenAPI 'string'
        $this->assertMatchesRegularExpression('/body:\s+type: string/', $yaml);
    }
}