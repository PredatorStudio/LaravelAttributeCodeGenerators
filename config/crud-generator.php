<?php

return [
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
