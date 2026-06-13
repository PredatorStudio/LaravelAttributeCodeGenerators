# Changelog – bieżąca iteracja zmian

## Postęp

- [x] 1. `SoftDeletes` → `UseSoftDeletes` (zmiana nazwy atrybutu)
- [x] 2. `BackedEnum` – argument `filename`
- [x] 3. `fields()` – definicja relacji (model, localKey, foreignKey)
- [x] 4. Return types na każdej metodzie
- [x] 5. Ścieżki plików bazowane na podkatalogu modelu
- [x] 6. `BindingsCollector` – dopisuj zamiast nadpisywać
- [x] 7. Config – klucze `paths` dla każdego generatora
- [x] 8. Config – `generate_php_docs`
- [x] 9. Aktualizacja readme.md

---

## Szczegóły

### 1. `SoftDeletes` → `UseSoftDeletes`
- Nowy plik: `src/Attributes/UseSoftDeletes.php`
- Zaktualizowane: `AttributeReader`, `ModelModifier`

### 2. `BackedEnum` – `filename`
```php
#[BackedEnum(field: 'status', values: ['active', 'inactive'], filename: 'ProjectStatus')]
```
- Dodany argument `?string $filename = null`
- `EnumGenerator`, `ModelProcessor` (plan/process/generateEnum), `ModelModifier::addCasts()`

### 3. `fields()` – relacja
```php
['type' => 'foreignId', 'name' => 'author_id', 'relation' => [
    'model'   => 'User',      // powiązany model (domyślnie auto-derive z _id)
    'local'   => 'author_id', // kolumna w tej tabeli (domyślnie: field name)
    'foreign' => 'id',        // kolumna w tabeli relacji (domyślnie: id)
]]
```
- Zmiana w `ModelModifier::addRelations()`

### 4. Return types
- Controller: `index(): AnonymousResourceCollection`, `show/store/update(): {Model}Resource`, `destroy(): Response`
- Service: `index(): LengthAwarePaginator`, `show/store/update(): {Model}`, `delete(): void`
- Repository: `query(): Builder`, `paginate(): LengthAwarePaginator`

### 5. Ścieżki bazowane na modelu
- Model `App\Models\Projects\Project` → kontroler w `Http/Controllers/Projects/ProjectController`
- Namespace generowanego pliku wyznaczany z konfiguracji + podkatalogu modelu
- Stuby używają `{{namespace}}` zamiast hardcoded `namespace App\...`

### 6. `BindingsCollector` – zachowanie ręcznych bindingów
- Generowany blok owijany znacznikami `// @crud-generator:start` / `// @crud-generator:end`
- Zawartość poza blokiem (ręczne bindingi) jest zachowywana przy kolejnych uruchomieniach

### 7. Config – `paths`
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

### 8. Config – `generate_php_docs`
```php
'generate_php_docs' => false,
```
Gdy `true`, każda wygenerowana metoda dostaje blok PHPDoc z `@param` i `@return`.