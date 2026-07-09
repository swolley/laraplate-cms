<?php

declare(strict_types=1);

return [
    'name' => 'CMS',

    'slugger' => env('CMS_SLUGGER', '\Illuminate\Support\Str::slug'),

    'import' => [
        'locale' => env('CMS_IMPORT_LOCALE', env('APP_LOCALE', 'en')),
        'default_contributor' => [
            'external_id' => (int) env('CMS_IMPORT_DEFAULT_CONTRIBUTOR_EXTERNAL_ID', 1),
            'name' => env('CMS_IMPORT_DEFAULT_CONTRIBUTOR_NAME', 'Redazione'),
            'slug' => env('CMS_IMPORT_DEFAULT_CONTRIBUTOR_SLUG', 'redazione'),
            'source_type' => env('CMS_IMPORT_DEFAULT_CONTRIBUTOR_SOURCE_TYPE', 'cms_default'),
        ],
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
