<?php

declare(strict_types=1);

return [
    'name' => 'CMS',

    'slugger' => env('CMS_SLUGGER', '\Illuminate\Support\Str::slug'),

    'locale' => [
        'auto_translate' => env('LOCALE_AUTO_TRANSLATE', false),
    ],
];
