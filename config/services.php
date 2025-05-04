<?php

declare(strict_types=1);

return [
    'geocoding' => [
        'provider' => env('GEOCODING_PROVIDER', 'nominatim'),
        'api_key' => env('GEOCODING_API_KEY'),
    ],
];
