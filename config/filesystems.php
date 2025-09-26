<?php

declare(strict_types=1);

return [
    'disks' => [
        'media-library' => [
            'driver' => 'local',
            'root' => storage_path('app/media'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],
    ],
];
