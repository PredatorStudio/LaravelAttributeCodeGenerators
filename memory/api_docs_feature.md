---
name: api-docs-feature
description: #[ApiDocs] attribute added May 2026 — generates per-model YAML + main openapi.yaml from controller methods
metadata:
  type: project
---

Added `#[ApiDocs]` attribute feature (2026-05-31).

**New files:**
- `src/Attributes/ApiDocs.php` — attribute with optional `description` param
- `config/crud-generator.php` — publishable config with 3 keys
- `src/Generators/ApiDocsGenerator.php` — generates `{Model}.yaml` (paths + components/schemas)
- `src/Generators/ApiDocsCollector.php` — collects models, flushes main `openapi.yaml`

**Config keys:**
- `api_docs_path` — base dir for docs
- `api_docs_models_path` — where `{Model}.yaml` files go (default `docs/api/models`)
- `api_docs_main_file` — main OpenAPI root file (default `docs/api/openapi.yaml`)

**How:** ApiDocsCollector is a singleton injected into ModelProcessor constructor (same instance in CrudGenerator). CrudGenerator calls `apiDocs->flush()` at the end of `flushOutputs()`. ModelProcessor calls `apiDocsGenerator->generate()` then `apiDocsCollector->add()` when `#[ApiDocs]` is present.

**Why:** `ApiDocsCollector` injected via constructor (not passed as param to `process()`) to avoid breaking existing tests that call `process()` with 4 args.

Config paths support both absolute and relative values — absolute paths bypass `base_path()`.