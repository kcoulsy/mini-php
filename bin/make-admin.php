<?php

declare(strict_types=1);

use App\Models\User;
use Framework\Autoloader;
use Framework\Database;

$basePath = dirname(__DIR__);

require $basePath . '/framework/Autoloader.php';

Autoloader::register('Framework\\', $basePath . '/framework');
Autoloader::register('App\\', $basePath . '/app');
spl_autoload_register([Autoloader::class, 'load']);

/** @var array<string, mixed> $config */
$config = require $basePath . '/config/app.php';

Database::connect($config['database']);

$email = $argv[1] ?? null;
$password = $argv[2] ?? null;
$name = $argv[3] ?? 'Admin';

if ($email === null || $password === null) {
    fwrite(STDERR, "Usage: php bin/make-admin.php <email> <password> [name]\n");
    fwrite(STDERR, "Promotes an existing user to admin, or creates a new admin account.\n");
    exit(1);
}

$existing = User::findByEmail($email);

if ($existing !== null) {
    User::update((int) $existing['id'], $email, $name, User::ROLE_ADMIN, null);
    fwrite(STDOUT, "Promoted existing user to admin: {$email}\n");
    exit(0);
}

$id = User::createWithRole($email, $password, User::ROLE_ADMIN, $name);
fwrite(STDOUT, "Created admin user #{$id}: {$email}\n");
