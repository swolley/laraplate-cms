<?php

declare(strict_types=1);

return [
    'name' => 'CMS',

    'slugger' => env('CMS_SLUGGER', '\Illuminate\Support\Str::slug'),

    'import' => [
        'locale' => env('CMS_IMPORT_LOCALE', env('APP_LOCALE', 'en')),
        'default_contributor' => [
            'name' => env('CMS_IMPORT_DEFAULT_CONTRIBUTOR_NAME', 'Redazione'),
            'slug' => env('CMS_IMPORT_DEFAULT_CONTRIBUTOR_SLUG', 'redazione'),
        ],
        /**
         * Contributor display names that may be reused across import sources.
         *
         * @var list<string>
         */
        'contributor_dedup_names' => array_values(array_filter(array_map(
            static fn (string $name): string => mb_trim($name),
            explode(',', (string) env('CMS_IMPORT_CONTRIBUTOR_DEDUP_NAMES', 'Redazione')),
        ))),
        'post_import' => [
            'clear_caches' => (bool) env('CMS_IMPORT_CLEAR_CACHES', true),
            'reindex' => (bool) env('CMS_IMPORT_REINDEX', false),
        ],
        /**
         * When true, bulk importers skip content whose origin is already registered.
         * Override per run with --arg force=1 on the importer plugin.
         */
        'skip_existing' => (bool) env('CMS_IMPORT_SKIP_EXISTING', true),
    ],
];
