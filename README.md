# Laravel Attribute Code Generators

A Laravel package that generates a full CRUD scaffold from PHP 8.1 Attributes placed directly on Eloquent models. One command reads every model in your app, inspects its attributes, and writes controllers, services, repositories, DTOs, resources, migrations, policies, observers, factories, tests, and routes — only what you asked for, nothing more.

## Requirements

- PHP 8.1+
- Laravel 10 or 11

## Installation

```bash
composer require predatorstudio/laravel-attribute-code-generators
```

The service provider is auto-discovered via Laravel's package auto-discovery.

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
#[Crud]                                                    // all methods
#[Crud(methods: ['index', 'store', 'show', 'update', 'destroy'])]
```

### `#[Route]`

Registers the resource route under the given path.

```php
#[Route(path: 'users')]
```

### `#[Resource]`

Generates an API resource class. Specify which fields to expose.

```php
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

Generates a Data Transfer Object. By default it is immutable and can be constructed from a request.

```php
#[DTO]
#[DTO(fromRequest: true, immutable: true)]
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

Generates an Eloquent factory for the model.

```php
#[Factory]
```

### `#[SoftDeletes]`

Adds soft-delete support to the generated migration and model scaffold.

```php
#[SoftDeletes]
```

### `#[GenerateMigration]`

Generates a migration based on the model's `fields()` method.

```php
#[GenerateMigration]
```

### `#[ValidateFromMigration]`

Generates `StoreRequest` and `UpdateRequest` validation rules derived from the migration columns.

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

Generates a single-purpose action class for the model.

```php
#[Action]
```

### `#[GenerateTest]`

Generates a feature test for the model's CRUD endpoints.

```php
#[GenerateTest]
```

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
use Vendor\LaravelAttributeCodeGenerators\Attributes\Service;
use Vendor\LaravelAttributeCodeGenerators\Attributes\SoftDeletes;
use Vendor\LaravelAttributeCodeGenerators\Attributes\ValidateFromMigration;

#[Crud(methods: ['index', 'store', 'show', 'update', 'destroy'])]
#[Route(path: 'users')]
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
#[GenerateTest]
class User extends Model
{
    public function fields(): array
    {
        return [
            ['name' => 'id',    'type' => 'id'],
            ['name' => 'name',  'type' => 'string'],
            ['name' => 'email', 'type' => 'string', 'unique' => true],
            ['name' => 'bio',   'type' => 'text',   'nullable' => true],
        ];
    }
}
```

Running `php artisan crud:sync` on this model generates:

- `UserController` with the five resource methods
- `UserService` + `UserServiceInterface` (bound in the container)
- `UserRepository` + `UserRepositoryInterface` (bound in the container)
- `UserResource`
- `UserDTO`
- `StoreUserRequest` and `UpdateUserRequest` with rules from the migration
- `UserPolicy`
- `UserObserver`
- `UserFactory`
- `StatusEnum` backed enum
- A feature test `UserTest`
- A migration file
- Resource routes registered under `/users`

## License

MIT
