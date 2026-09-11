<?php

return [
    'default' => env('QUEUE_CONNECTION'),
    'connections' => [
        'sync' => ['driver' => 'sync'],
        'redis' => ['driver' => 'redis', 'connection' => 'default', 'queue' => env('REDIS_QUEUE'), 'retry_after' => 90],
    ],
];
