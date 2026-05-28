<?php

declare(strict_types=1);

use Framework\Testing\TestRunner;

require __DIR__ . '/bootstrap.php';

$paths = array_slice($argv, 1);

if ($paths === []) {
    $paths = [
        __DIR__ . '/Unit',
        __DIR__ . '/Feature',
    ];
}

$exit = (new TestRunner())->run($paths);

exit($exit);
