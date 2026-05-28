<?php

declare(strict_types=1);

use Database\Seed\DemoSeeder;
use Framework\Autoloader;
use Framework\Database;
use Framework\Migrations\Migrator;

$basePath = dirname(__DIR__);

require $basePath . '/framework/Autoloader.php';

Autoloader::register('Framework\\', $basePath . '/framework');
Autoloader::register('App\\', $basePath . '/app');
Autoloader::register('Database\\', $basePath . '/database');
spl_autoload_register([Autoloader::class, 'load']);

/** @var array<string, mixed> $config */
$config = require $basePath . '/config/app.php';

Database::connect($config['database']);

$fresh = in_array('--fresh', $argv, true);

$ran = Migrator::fromPath(Database::pdo(), $basePath . '/database/migrations')->up();

foreach ($ran as $name) {
    fwrite(STDOUT, "Migrated: {$name}\n");
}

if ($ran !== []) {
    fwrite(STDOUT, count($ran) . " migration(s) applied.\n\n");
}

DemoSeeder::run($fresh);
