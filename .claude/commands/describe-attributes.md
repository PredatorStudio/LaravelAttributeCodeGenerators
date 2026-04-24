Read every PHP file in `src/Attributes/` of this repository and produce a structured description of the whole attribute system.

For each attribute output:

1. **Attribute name** as a heading — e.g. `#[Crud]`
2. **What it generates** — a short sentence listing the files/artifacts that are created when this attribute is present on a model.
3. **Parameters** — a table with columns `Parameter | Type | Default | Description` for every constructor argument. If the attribute has no parameters, write "No parameters."
4. **Usage examples** — one or two concise PHP snippets showing the most common ways to apply the attribute.
5. **Notes** — any important behaviours: idempotency (re-run skips), interaction with other attributes, PATCH semantics, `hidden` flag, ALTER migrations, etc. Only include this section when there is something non-obvious to say.

After all individual attribute descriptions, add a **`fields()` reference** section that documents every key accepted in a field array (`name`, `type`, `nullable`, `unique`, `default`, `hidden`) and how each key affects generation (migration column, validation rules, DTO, resource output).

Finally, add a **Attribute interaction map** — a compact table showing which attributes depend on or complement each other (e.g. `#[GenerateMigration]` + `#[ValidateFromMigration]`, `#[Service(interface:true)]` + `#[Repository(interface:true)]` + bindings, etc.).

Keep the language concise and technical. Assume the reader is a Laravel developer who knows PHP 8 Attributes syntax but has not used this package before.

If `$ARGUMENTS` is not empty, focus only on the attribute whose name matches the argument (case-insensitive, ignore the `#[` `]` wrapper). For example `/describe-attributes Seeder` should describe only `#[Seeder]`.
