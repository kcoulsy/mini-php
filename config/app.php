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
    'session' => [
        'name' => 'miniphp_sid',
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => null,
        'httponly' => true,
        'samesite' => 'Lax',
    ],
    'auth' => [
        'password_min_length' => 8,
    ],
    'uploads' => [
        'path' => dirname(__DIR__) . '/storage/uploads',
        'max_bytes' => 5_242_880,
        'max_files_per_request' => 10,
        'allowed_mimes' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
        ],
    ],
    'security' => [
        'csrf' => true,
        'headers' => true,
        'csp' => "default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; img-src 'self' blob: data:",
        'hsts' => false,
    ],
];
