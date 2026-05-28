<?php

declare(strict_types=1);

use Framework\Database;
use Framework\Migrations\Migrator;

$basePath = dirname(__DIR__);

Migrator::fromPath(Database::pdo(), $basePath . '/database/migrations')->up();
