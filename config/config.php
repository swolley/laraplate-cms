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
    ],

    // 'locale' => [
    //     'auto_translate' => env('LOCALE_AUTO_TRANSLATE', false),
    // ],

    // 'geocoding' => [
    //     /** TTL in seconds for geocoding cache results. Default: 7 days (604800 seconds). */
    //     'cache_ttl' => (int) env('CMS_GEOCODING_CACHE_TTL', 604800),
    // ],
];
