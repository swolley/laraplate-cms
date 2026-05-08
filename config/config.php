<?php

declare(strict_types=1);

return [
    'name' => 'CMS',

    'slugger' => env('CMS_SLUGGER', '\Illuminate\Support\Str::slug'),

    'locale' => [
        'auto_translate' => env('LOCALE_AUTO_TRANSLATE', false),
    ],

    'geocoding' => [
        /** TTL in seconds for geocoding cache results. Default: 7 days (604800 seconds). */
        'cache_ttl' => (int) env('CMS_GEOCODING_CACHE_TTL', 604800),
    ],
];
