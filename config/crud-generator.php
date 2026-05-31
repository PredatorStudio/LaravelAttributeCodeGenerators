<?php

return [
    /*
     * Directory to scan for models with CRUD attributes (relative to project root).
     * All subdirectories are scanned recursively. Namespace is derived automatically.
     */
    'scan_path' => 'app/Models',

    /*
     * Base directory for all API documentation files (relative to project root).
     */
    'api_docs_path' => 'docs/api',

    /*
     * Directory where per-model YAML files are saved (e.g. Post.yaml).
     */
    'api_docs_models_path' => 'docs/api/models',

    /*
     * Main OpenAPI file that organises the entire API (regenerated on every crud:sync).
     */
    'api_docs_main_file' => 'docs/api/openapi.yaml',
];
