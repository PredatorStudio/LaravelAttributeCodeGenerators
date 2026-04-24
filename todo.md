# TODO
*V1*

## [X] 1. `--dry-run` / `--force` flags on artisan command
- `--dry-run` — shows planned files without writing anything (uses existing `plan()` in ModelProcessor)
- `--force` — prompts confirmation when a file already exists before overwriting
- Add conflict detection in `FileWriter` (check if file exists, skip/warn by default)

## [X] 2. Interface generation for Service / Repository
- Option: `#[Service(interface: true)]` or separate `#[Contract]` attribute
- Generates `UserServiceInterface` + `UserRepositoryInterface`
- Adds binding in service provider (`$this->app->bind(UserServiceInterface::class, UserService::class)`)
- Service/Repository constructors type-hint the interface, not the concrete class

## [X] 3. Model self-modification
- Generator reads the model but never writes to it — this is the biggest gap
- Should automatically add:
  - `$fillable` based on `fields()`
  - `$casts` linked to `#[BackedEnum]` fields (e.g. `'status' => UserStatus::class`)
  - `use SoftDeletes` trait when `#[SoftDeletes]` is present
  - Relationship methods based on `foreignId` fields in `fields()`
- Requires a PHP file parser/modifier (e.g. nikic/php-parser) to safely inject into existing class

## [X] 4. Zapis wykonanych już generowań
- Po poprawnym wygenerowaniu kodu powinniśmy zapisywać co zostało wykonane, np. dla modelu User został dodany Service oraz Enum i Controller
- Następne wywołanie polecenia nie powinno już tego generować, powinno przeskanować aplikacje, zebrać dane, sprawdzić co było już wykonane, zrobić diff i wygenerować kod
- Plik powinien być widoczny żeby w razie zmiany w modelu np. kolumn lub argumentów atrybutu można było usunąć je z zapisanego pliku i wygenerować ponownie

## [X] 5. Sprawdzanie migracji
- Jeśli mamy wygenerowaną migrację i dodamy modelu nową kolumnę system powinien mieć zapisane jakie kolumny są wygenerowane i przy ponownym generowaniu powinien wygenerować
- migrację wyłącznie na nowe kolumny

## [X] 6. Kolumna hidden
- jeśli ustawimy hidden na deklaracji kolumny to nie powinna być ona dodawana do request/response, mozna to lepiej nazwać ale ma działać tak jak napisane

## [X] 7. skill do AI który opisze wszystkie atrybuty
- Plik skill w `resources/ai/describe-attributes.md` — źródło prawdy wewnątrz paczki
- Komenda `php artisan crud:install --ai` kopiuje skill do `.claude/commands/describe-attributes.md` projektu użytkownika
- Po restarcie Claude Code dostępny jako `/describe-attributes` (wszystkie atrybuty) lub `/describe-attributes Seeder` (konkretny atrybut)
- Skill generuje: opis generowanych artefaktów, tabelę parametrów, przykłady użycia, mapę interakcji między atrybutami oraz referencję `fields()`

## [X] 8. Generowanie Seederów i factory

## [X] 9. MD - opis biblioteki, atrybutów itp.

## [X] 11. `hidden` nie działa w DTO — DtoBuilder nie sprawdza flagi `hidden: true`, niespójność z punktem 6

## [X] 12. `UpdateRequest` powinien używać `sometimes` — partial update (PATCH semantics), teraz Store i Update mają te same reguły

## [X] 13. `#[SoftDeletes]` nie dodaje `$table->softDeletes()` w migracji — kolumna `deleted_at` w ogóle nie pojawia się w migracji

## [X] 14. `foreignId` w Factory generuje `fake()->word()` — zły typ, powinno być `fake()->randomNumber()`

## [X] 15. Controller ignoruje metody z `#[Crud(methods: [...])]` — route filtruje przez `->only()` ale kontroler zawsze ma wszystkie 5 metod

## [X] 16. `#[Route(middleware: [...])]` — brak wsparcia dla middleware w generowanych trasach

## [ ] 10. Landing page biblioteki + opis wszystkich attributes

*V2*
## [ ] 11. Rozszerzenie do Livewire - automatyczne generowanie Komponentów z formularzem
## [ ] 12. Laravel excel - automatyczne generowanie Exportu i importu modelu
## [ ] 13. Automatyczne generowanie datatable, dla Livewire korzystamy z LwTable, dla laravel stworzyć trzeba swoją bibliotekę na bazie LWTable
## [ ] 14. Atrybut 'WithActionLog([MethodsToLogs (show/create/update/delete)])' - dodaje do serwisów logowanie każdej akcji (https://spatie.be/docs/laravel-activitylog/v5/introduction)
## [ ] 15. Atrybut 'WithPermissions([permissions (create/delete/update/view/download)])' - Automatycznie tworzy migracje która doda uprawnienia dla danego modelu, np spatie laravel permissions
## [ ] 16. Atrybut 'RestAPI(version, methods)' (tworzy kontroler ApiResource z odpowiednimi metodami i routem oraz generuje pliki yaml swagger dla modelu)
## [ ] 17. Atrybut 'GraphQLAPI(version)' - tworzy kontroler z wykorzystaniem nuwave/lighthouse - technologia GraphQL
## [ ] 18. Atrybut 'UnitTests' - automatyczne tworzenie testów CRUD dla modelu
