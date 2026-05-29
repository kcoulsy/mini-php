<?php

declare(strict_types=1);

// PHP built-in server: serve existing files under public/; route everything else here.
if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $file = __DIR__ . $path;

    if ($path !== '/' && is_file($file)) {
        return false;
    }
}

use Framework\App;
use Framework\Autoloader;
use Framework\Session;

$basePath = dirname(__DIR__);

require $basePath . '/framework/Autoloader.php';

Autoloader::register('Framework\\', $basePath . '/framework');
Autoloader::register('App\\', $basePath . '/app');

spl_autoload_register([Autoloader::class, 'load']);

require $basePath . '/framework/Validation/Attributes/ValidationAttributes.php';

/** @var array<string, mixed> $config */
$config = require $basePath . '/config/app.php';

/** @var array<string, mixed> $sessionConfig */
$sessionConfig = $config['session'] ?? [];
Session::start($sessionConfig);

$app = new App($config);
$app->registerRoutes();
$app->run();
