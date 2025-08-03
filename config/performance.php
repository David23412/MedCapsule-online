<?php
/**
 * Performance-optimized configuration
 * This file contains optimized settings for maximum performance
 */

return [
    'app' => [
        'debug' => false,
        'log_level' => 'warning',
    ],
    'cache' => [
        'default' => 'redis',
        'stores' => [
            'redis' => [
                'driver' => 'redis',
                'connection' => 'cache',
                'lock_connection' => 'default',
            ],
        ],
    ],
    'session' => [
        'driver' => 'redis',
        'connection' => 'default',
    ],
    'database' => [
        'connections' => [
            'mysql' => [
                'options' => [
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                ],
            ],
        ],
    ],
];