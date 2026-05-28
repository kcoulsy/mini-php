<?php

declare(strict_types=1);

use Framework\Testing\TestRunner;

require __DIR__ . '/bootstrap.php';

$verbose = true;
$paths = [];

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--quiet' || $arg === '-q') {
        $verbose = false;
        continue;
    }

    $paths[] = $arg;
}

if ($paths === []) {
    $paths = [
        __DIR__ . '/Framework',
        __DIR__ . '/App',
        __DIR__ . '/Feature',
    ];
}

$exit = (new TestRunner())->run($paths, verbose: $verbose);

exit($exit);
