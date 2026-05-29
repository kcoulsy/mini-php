<?php

declare(strict_types=1);

use Framework\App;
use Framework\Autoloader;
use Framework\Session;

require dirname(__DIR__) . '/framework/Autoloader.php';

Autoloader::register('Framework\\', dirname(__DIR__) . '/framework');
Autoloader::register('App\\', dirname(__DIR__) . '/app');

spl_autoload_register([Autoloader::class, 'load']);

require dirname(__DIR__) . '/framework/Validation/Attributes/ValidationAttributes.php';

/** @var array<string, mixed> $config */
$config = require dirname(__DIR__) . '/config/app.php';

/** @var array<string, mixed> $sessionConfig */
$sessionConfig = $config['session'] ?? [];
Session::start($sessionConfig);

$app = new App($config);

require dirname(__DIR__) . '/database/migrate.php';
require dirname(__DIR__) . '/routes/web.php';

return $app;
