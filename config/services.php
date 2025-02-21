<?php

return [
    'geocoding' => [
        'provider' => env('GEOCODING_PROVIDER', 'nominatim'),
        'api_key' => env('GEOCODING_API_KEY'),
    ],
];
