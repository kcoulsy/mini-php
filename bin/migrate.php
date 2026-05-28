<?php

declare(strict_types=1);

use Framework\Autoloader;
use Framework\Database;
use Framework\Migrations\Migrator;

$basePath = dirname(__DIR__);

require $basePath . '/framework/Autoloader.php';

Autoloader::register('Framework\\', $basePath . '/framework');
spl_autoload_register([Autoloader::class, 'load']);

/** @var array<string, mixed> $config */
$config = require $basePath . '/config/app.php';

Database::connect($config['database']);

$migrationsPath = $basePath . '/database/migrations';
$ran = Migrator::fromPath(Database::pdo(), $migrationsPath)->up();

if ($ran === []) {
    fwrite(STDOUT, "Nothing to migrate.\n");
    exit(0);
}

foreach ($ran as $name) {
    fwrite(STDOUT, "Migrated: {$name}\n");
}

fwrite(STDOUT, count($ran) . " migration(s) applied.\n");
