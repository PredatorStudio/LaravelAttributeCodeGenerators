<?php

return [
    /*
     * Directory to scan for models with CRUD attributes (relative to project root).
     * All subdirectories are scanned recursively. Namespace is derived automatically.
     */
    'scan_path' => 'app/Models',

    /*
     * Output directories for each generator (relative to project root).
     * Subdirectories mirroring the model's location are appended automatically.
     * Example: model at app/Models/Projects/Project → controller at app/Http/Controllers/Projects/
     */
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

    /*
     * When true, every generated method will include a standard PHPDoc block
     * with @param and @return annotations.
     */
    'generate_php_docs' => false,

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