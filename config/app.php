<?php

declare(strict_types=1);

return [
    'name' => 'MiniPHP',
    'debug' => true,
    'base_path' => dirname(__DIR__),
    'database' => [
        'driver' => 'sqlite',
        'path' => dirname(__DIR__) . '/storage/database.sqlite',
    ],
];
