# Laravel Attribute Code Generators

**v1.0**

A Laravel package that generates a full CRUD scaffold from PHP 8.2 Attributes placed directly on Eloquent models. One command reads every model in your app, inspects its attributes, and writes controllers, services, repositories, DTOs, resources, migrations, policies, observers, factories, seeders, tests, and routes — only what you asked for, nothing more.

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13

## Installation

```bash
composer require predatorstudio/laravel-attribute-code-generators
```

The service provider is auto-discovered via Laravel's package auto-discovery.

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

The skill covers:
- What each attribute generates
- All constructor parameters with types, defaults, and descriptions
- Usage examples
- Non-obvious behaviours (idempotency, PATCH semantics, `hidden` flag, ALTER migrations, …)
- `fields()` key reference
- Attribute interaction map (which attributes depend on or complement each other)

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

Enables CRUD generation for the model. Optionally restricts which HTTP methods are scaffolded. The generated controller and resource routes will contain only the listed methods.

```php
#[Crud]                                                    // all methods
#[Crud(methods: ['index', 'store', 'show', 'update', 'destroy'])]
```

### `#[Route]`

Registers the resource route under the given path. Optionally applies middleware to the route group.

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

### `#[SoftDeletes]`

Adds soft-delete support: appends `$table->softDeletes()` to the generated migration and injects the `SoftDeletes` trait into the model.

```php
#[SoftDeletes]
```

### `#[GenerateMigration]`

Generates a `create_*_table` migration based on the model's `fields()` method. On subsequent runs the package compares saved column names against the current `fields()` list — if new columns are found it generates an `add_columns_to_*_table` ALTER migration instead of recreating the original.

```php
#[GenerateMigration]
```

### `#[ValidateFromMigration]`

Generates `StoreRequest` and `UpdateRequest` validation rules derived from the migration columns. The `UpdateRequest` automatically prepends `sometimes` to every rule, making it suitable for PATCH requests (only fields present in the payload are validated).

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

| Key | Type | Description |
|---|---|---|
| `name` | string | Column name |
| `type` | string | Column type (`string`, `text`, `integer`, `boolean`, `foreignId`, `json`, `id`, `timestamps`, …) |
| `nullable` | bool | Adds `nullable()` to the migration column and `nullable` to validation rules |
| `unique` | bool | Adds `unique()` to the migration column |
| `default` | mixed | Adds `->default(value)` to the migration column |
| `hidden` | bool | Excludes the field from requests, resources, and DTOs — useful for internal columns like hashed tokens or audit fields |

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
use Vendor\LaravelAttributeCodeGenerators\Attributes\SoftDeletes;
use Vendor\LaravelAttributeCodeGenerators\Attributes\ValidateFromMigration;

#[Crud(methods: ['index', 'store', 'show', 'update', 'destroy'])]
#[Route(path: 'users', middleware: ['auth:sanctum'])]
#[Resource(fields: ['id', 'name', 'email'])]
#[Service(interface: true)]
#[Repository(interface: true)]
#[Policy]
#[ValidateFromMigration]
#[DTO]
#[SoftDeletes]
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
            ['type' => 'timestamps'],
        ];
    }
}
```

Running `php artisan crud:sync` on this model generates:

- `UserController` with the five resource methods
- `UserService` + `UserServiceInterface` (bound in the container)
- `UserRepository` + `UserRepositoryInterface` (bound in the container)
- `UserResource` exposing `id`, `name`, `email` (password excluded via `hidden`)
- `UserDTO` with readonly properties (password excluded via `hidden`)
- `UserStoreRequest` with validation rules from the migration
- `UserUpdateRequest` with the same rules prefixed with `sometimes` for PATCH semantics
- `UserPolicy`
- `UserObserver`
- `UserFactory` with faker values per column type
- `UserSeeder` creating 20 records via the factory
- `UserStatusEnum` backed enum
- A feature test `UserTest`
- A migration file with `softDeletes()`
- Resource routes registered under `/users` behind the `auth:sanctum` middleware

On the next run, already-generated artifacts are skipped. If new columns are added to `fields()`, only an ALTER migration is generated for the new columns.

## License

MIT
