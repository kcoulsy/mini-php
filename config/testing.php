<?php

declare(strict_types=1);

/** @var array<string, mixed> $config */
$config = require __DIR__ . '/app.php';

$config['database'] = [
    'driver' => 'sqlite',
    'path' => ':memory:',
];

$config['uploads']['path'] = sys_get_temp_dir() . '/miniphp-test-uploads-' . getmypid();

return $config;
