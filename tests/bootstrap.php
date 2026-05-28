<?php

declare(strict_types=1);

use Framework\App;
use Framework\Autoloader;
use Framework\Database;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/framework/Autoloader.php';

Autoloader::register('Framework\\', BASE_PATH . '/framework');
Autoloader::register('App\\', BASE_PATH . '/app');
Autoloader::register('Tests\\', BASE_PATH . '/tests');

spl_autoload_register([Autoloader::class, 'load']);

function createTestApplication(): App
{
    Database::disconnect();

    /** @var array<string, mixed> $config */
    $config = require BASE_PATH . '/config/testing.php';

    $app = new App($config);

    require BASE_PATH . '/database/migrate.php';
    require BASE_PATH . '/routes/web.php';

    return $app;
}
