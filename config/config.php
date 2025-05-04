<?php

declare(strict_types=1);

return [
    'name' => 'Cms',

    'slugger' => env('CMS_SLUGGER', '\Illuminate\Support\Str::slug'),
];
