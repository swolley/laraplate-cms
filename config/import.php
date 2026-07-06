<?php

declare(strict_types=1);

return [

    'locale' => env('CMS_IMPORT_LOCALE', env('APP_LOCALE', 'en')),

    'default_contributor' => [
        'external_id' => (int) env('CMS_IMPORT_DEFAULT_CONTRIBUTOR_ID', 0),
        'name' => env('CMS_IMPORT_DEFAULT_CONTRIBUTOR_NAME', 'Redazione'),
        'slug' => env('CMS_IMPORT_DEFAULT_CONTRIBUTOR_SLUG', 'redazione'),
    ],

    'post_import' => [
        'clear_caches' => (bool) env('CMS_IMPORT_CLEAR_CACHES', true),
        'reindex' => (bool) env('CMS_IMPORT_REINDEX', false),
    ],

    /**
     * Optional preset field definitions provisioned at import time.
     *
     * @var array<string, array<string, array<string, array{type: string, translatable?: bool, required?: bool}>>>
     */
    'presets' => [],

    /**
     * Target CMS entity + preset names written on import DTOs by source mappers.
     */
    'bindings' => [
        'contents' => [],
        'taxonomies' => [],
        'contributors' => [
            'contributor' => [
                'entity' => env('CMS_IMPORT_CONTRIBUTOR_ENTITY', 'contributor'),
                'preset' => env('CMS_IMPORT_CONTRIBUTOR_PRESET', 'default'),
            ],
        ],
    ],

];
