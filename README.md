# Laravel Attribute Code Generators

**v1.1**

A Laravel package that generates a full CRUD scaffold from PHP 8.1 Attributes placed directly on Eloquent models. One command reads every model in your app, inspects its attributes, and writes controllers, services, repositories, DTOs, resources, migrations, policies, observers, factories, seeders, tests, and routes — only what you asked for, nothing more.

## Requirements

- PHP 8.1+
- Laravel 10+

## Installation

```bash
composer require predatorstudio/laravel-attribute-code-generators
```

The service provider is auto-discovered via Laravel's package auto-discovery.

Publish the config file to customise paths:

```bash
php artisan vendor:publish --tag=crud-generator-config
```

This creates `config/crud-generator.php` in your application.

## Claude Code integration (optional)

Install a `/describe-attributes` slash command into your project's Claude Code environment:

```bash
php artisan crud:install --ai
```

This copies a prompt file to `.claude/commands/describe-attributes.md` in your project root. After restarting Claude Code the command is available as a slash command:

| Command | Description |
|---|---|
| `/describe-attributes` | Full reference — every attribute, its parameters, generated artifacts, and interaction map |
| `/describe-attributes Seeder` | Reference for a single attribute (case-insensitive) |

## Configuration

After publishing the config you can adjust these keys in `config/crud-generator.php`:

| Key | Default | Description |
|---|---|---|
| `scan_path` | `app/Models` | Directory scanned for models (relative to project root). All subdirectories are included recursively. |
| `generate_php_docs` | `false` | When `true`, every generated method gets a PHPDoc block with `@param` and `@return` annotations. |
| `api_docs_path` | `docs/api` | Base directory for API documentation files |
| `api_docs_models_path` | `docs/api/models` | Directory where per-model YAML files are saved |
| `api_docs_main_file` | `docs/api/openapi.yaml` | Main OpenAPI file regenerated on every `crud:sync` |

### Output path overrides

The `paths` key lets you change the target directory for each generator. All paths are relative to the project root.

```php
'paths' => [
    'controllers'  => 'app/Http/Controllers',
    'resources'    => 'app/Http/Resources',
    'requests'     => 'app/Http/Requests',
    'services'     => 'app/Services',
    'repositories' => 'app/Repositories',
    'contracts'    => 'app/Contracts',
    'policies'     => 'app/Policies',
    'observers'    => 'app/Observers',
    'actions'      => 'app/Actions',
    'dto'          => 'app/DTO',
    'enums'        => 'app/Enums',
],
```

Example — if your services live in `app/Libs/Services`:

```php
'paths' => [
    'services' => 'app/Libs/Services',
],
```

The generated `UserService` will be placed at `app/Libs/Services/UserService.php` with namespace `App\Libs\Services`.

### Scanning subdirectories & automatic subpath mirroring

Models can be placed in any subdirectory under `scan_path`. The namespace is inferred from the folder structure, and **every generated file mirrors the same subdirectory**:

```
app/Models/Projects/Project.php  →  App\Models\Projects\Project
```

Generated files:

```
app/Http/Controllers/Projects/ProjectController.php
app/Services/Projects/ProjectService.php
app/Repositories/Projects/ProjectRepository.php
...
```

This ensures namespaces stay consistent across the entire generated scaffold without any manual configuration.

## Usage

Annotate your model with the attributes that describe what you want generated, then run:

```bash
php artisan crud:sync
```

| Option | Description |
|---|---|
| `--dry-run` | Preview what would be generated without writing any files |
| `--force` | Overwrite existing files (asks for confirmation per file) |

## Available Attributes

### `#[Crud]`

Enables CRUD generation for the model. Optionally restricts which HTTP methods are scaffolded.

```php
#[Crud]
#[Crud(methods: ['index', 'store', 'show', 'update', 'destroy'])]
```

### `#[Route]`

Registers the resource route under the given path. Optionally applies middleware.

```php
#[Route(path: 'users')]
#[Route(path: 'users', middleware: ['auth:sanctum', 'verified'])]
```

### `#[Resource]`

Generates an API resource class. Specify which fields to expose, or omit `fields` to auto-generate from the model's visible `fields()` columns (respecting `hidden: true`).

```php
#[Resource]
#[Resource(fields: ['id', 'name', 'email'])]
```

### `#[Service]`

Generates a service class. Pass `interface: true` to also generate a contract and bind it in the service container.

```php
#[Service]
#[Service(interface: true)]
```

### `#[Repository]`

Generates a repository class. Pass `interface: true` to also generate a contract and bind it.

```php
#[Repository]
#[Repository(interface: true)]
```

### `#[DTO]`

Generates a Data Transfer Object with readonly properties, a `fromArray()` factory, and a `toArray()` method. Fields marked `hidden: true` in `fields()` are excluded.

```php
#[DTO]
```

### `#[Policy]`

Generates a policy class for the model.

```php
#[Policy]
```

### `#[Observer]`

Generates an observer class and registers it automatically.

```php
#[Observer]
```

### `#[Factory]`

Generates an Eloquent factory for the model with sensible faker defaults per column type.

```php
#[Factory]
```

### `#[Seeder]`

Generates a database seeder that uses the model's factory to create records. The default count is 10.

```php
#[Seeder]
#[Seeder(count: 50)]
```

### `#[UseSoftDeletes]`

Adds soft-delete support: appends `$table->softDeletes()` to the generated migration and injects the `SoftDeletes` trait into the model.

```php
#[UseSoftDeletes]
```

> **Note:** The attribute was renamed from `#[SoftDeletes]` to `#[UseSoftDeletes]` in v1.1 to avoid a name collision with the Eloquent `SoftDeletes` trait.

### `#[GenerateMigration]`

Generates a `create_*_table` migration based on the model's `fields()` method. On subsequent runs the package compares saved column names against the current `fields()` list — if new columns are found it generates an `add_columns_to_*_table` ALTER migration.

```php
#[GenerateMigration]
```

### `#[ValidateFromMigration]`

Generates `StoreRequest` and `UpdateRequest` validation rules derived from the migration columns. The `UpdateRequest` automatically prepends `sometimes` to every rule, making it suitable for PATCH requests.

Fields marked `hidden: true` in `fields()` are excluded from both requests.

```php
#[ValidateFromMigration]
```

### `#[BackedEnum]`

Generates a backed enum for a field. Repeatable — add one per enum field.

```php
#[BackedEnum(field: 'status', values: ['active', 'inactive'])]
#[BackedEnum(field: 'role',   values: ['admin', 'editor', 'viewer'], type: 'string')]
```

Use `filename` to override the generated class name (useful when the auto-derived name conflicts with something else):

```php
#[BackedEnum(field: 'status', values: ['active', 'inactive'], filename: 'ProjectStatus')]
// generates App\Enums\ProjectStatus instead of App\Enums\ProjectStatus (default)
```

### `#[Action]`

Generates single-purpose action classes (`Create`, `Update`, `Delete`) for the model.

```php
#[Action]
```

### `#[GenerateTest]`

Generates a feature test for the model's CRUD endpoints.

```php
#[GenerateTest]
```

---

## The `fields()` method

Define the model's schema by implementing a `fields()` method. It drives migration generation, validation rules, factories, DTOs, and resource output.

```php
public function fields(): array
{
    return [
        ['type' => 'id'],
        ['name' => 'title',       'type' => 'string'],
        ['name' => 'body',        'type' => 'text',      'nullable' => true],
        ['name' => 'status',      'type' => 'string',    'default' => 'draft'],
        ['name' => 'user_id',     'type' => 'foreignId'],
        ['name' => 'secret_hash', 'type' => 'string',    'hidden' => true],
        ['type' => 'timestamps'],
    ];
}
```

### Field keys

| Key | Type | Description |
|---|---|---|
| `name` | string | Column name |
| `type` | string | Column type (`string`, `text`, `integer`, `boolean`, `foreignId`, `json`, `id`, `timestamps`, …) |
| `nullable` | bool | Adds `nullable()` to the migration column and `nullable` to validation rules |
| `unique` | bool | Adds `unique()` to the migration column |
| `default` | mixed | Adds `->default(value)` to the migration column |
| `hidden` | bool | Excludes the field from requests, resources, and DTOs |

### Explicit relation definitions

For `foreignId` fields you can provide an explicit `relation` key to control how the `BelongsTo` relation is generated on the model. Without it the related model is derived automatically from the `_id` suffix.

```php
['name' => 'author_id', 'type' => 'foreignId', 'relation' => [
    'model'   => 'User',      // related model class (default: auto-derived from field name)
    'local'   => 'author_id', // local key on this table (default: field name)
    'foreign' => 'id',        // key on the related table (default: id)
]]
```

When `local` or `foreign` differ from defaults the generated `belongsTo()` call includes the explicit key arguments:

```php
public function author(): BelongsTo
{
    return $this->belongsTo(User::class, 'author_id', 'id');
}
```

---

## Bindings provider

When `interface: true` is used on `#[Service]` or `#[Repository]`, the package writes interface-to-implementation bindings to `app/Providers/GeneratedBindingsProvider.php`.

The generated block is wrapped in marker comments so that **manually added bindings are preserved** across runs:

```php
public function register(): void
{
    // @crud-generator:start
    $this->app->bind(\App\Contracts\UserServiceInterface::class, \App\Services\UserService::class);
    // @crud-generator:end

    // your manual bindings go here and are never touched
    $this->app->bind(PaymentGatewayInterface::class, StripeGateway::class);
}
```

---

## Generated method signatures

All generated methods include explicit return types. Enable PHPDoc blocks via config:

```php
'generate_php_docs' => true,
```

Controller:

```php
/** @return AnonymousResourceCollection */
public function index(): AnonymousResourceCollection { ... }

/** @return UserResource */
public function show(User $user): UserResource { ... }

/** @return Response */
public function destroy(User $user): Response { ... }
```

Service:

```php
/** @return LengthAwarePaginator */
public function index(): \Illuminate\Contracts\Pagination\LengthAwarePaginator { ... }

/** @return User */
public function store(array $data): User { ... }
```

---

## Full example

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Action;
use Vendor\LaravelAttributeCodeGenerators\Attributes\BackedEnum;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Crud;
use Vendor\LaravelAttributeCodeGenerators\Attributes\DTO;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Factory;
use Vendor\LaravelAttributeCodeGenerators\Attributes\GenerateTest;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Observer;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Policy;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Repository;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Resource;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Route;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Seeder;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Service;
use Vendor\LaravelAttributeCodeGenerators\Attributes\UseSoftDeletes;
use Vendor\LaravelAttributeCodeGenerators\Attributes\ValidateFromMigration;

#[Crud(methods: ['index', 'store', 'show', 'update', 'destroy'])]
#[Route(path: 'users', middleware: ['auth:sanctum'])]
#[Resource(fields: ['id', 'name', 'email'])]
#[Service(interface: true)]
#[Repository(interface: true)]
#[Policy]
#[ValidateFromMigration]
#[DTO]
#[UseSoftDeletes]
#[BackedEnum(field: 'status', values: ['active', 'inactive'])]
#[Observer]
#[Action]
#[Factory]
#[Seeder(count: 20)]
#[GenerateTest]
class User extends Model
{
    public function fields(): array
    {
        return [
            ['type' => 'id'],
            ['name' => 'name',        'type' => 'string'],
            ['name' => 'email',       'type' => 'string', 'unique' => true],
            ['name' => 'bio',         'type' => 'text',   'nullable' => true],
            ['name' => 'password',    'type' => 'string', 'hidden' => true],
            ['name' => 'team_id',     'type' => 'foreignId', 'relation' => [
                'model' => 'Team',
            ]],
            ['type' => 'timestamps'],
        ];
    }
}
```

Running `php artisan crud:sync` on this model generates:

- `UserController` with the five resource methods and return types
- `UserService` + `UserServiceInterface` (bound in the container)
- `UserRepository` + `UserRepositoryInterface` (bound in the container)
- `UserResource` exposing `id`, `name`, `email` (password excluded via `hidden`)
- `UserDTO` with readonly properties (password excluded via `hidden`)
- `UserStoreRequest` + `UserUpdateRequest` with validation rules
- `UserPolicy`, `UserObserver`, `UserFactory`, `UserSeeder` (20 records)
- `UserStatusEnum` backed enum
- A feature test `UserTest`
- A migration with `softDeletes()`
- The `team()` relation injected into the model
- Routes registered under `/users` behind `auth:sanctum`

On the next run, already-generated artifacts are skipped. If new columns are added to `fields()`, only an ALTER migration is generated for the new columns.

## License

MIT
