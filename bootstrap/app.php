<?php

declare(strict_types=1);

use Framework\App;
use Framework\Autoloader;

require dirname(__DIR__) . '/framework/Autoloader.php';

Autoloader::register('Framework\\', dirname(__DIR__) . '/framework');
Autoloader::register('App\\', dirname(__DIR__) . '/app');

spl_autoload_register([Autoloader::class, 'load']);

/** @var array<string, mixed> $config */
$config = require dirname(__DIR__) . '/config/app.php';

$app = new App($config);

require dirname(__DIR__) . '/database/migrate.php';
require dirname(__DIR__) . '/routes/web.php';

return $app;
