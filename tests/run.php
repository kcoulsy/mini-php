<?php

declare(strict_types=1);

use Framework\Testing\TestRunner;

require __DIR__ . '/bootstrap.php';

$verbose = true;
$parallel = 0;
$paths = [];
$rawArgs = array_slice($argv, 1);

for ($i = 0; $i < count($rawArgs); $i++) {
    $arg = $rawArgs[$i];

    if ($arg === '--quiet' || $arg === '-q') {
        $verbose = false;
        continue;
    }

    if ($arg === '--parallel' || $arg === '-j') {
        if (isset($rawArgs[$i + 1]) && is_numeric($rawArgs[$i + 1])) {
            $parallel = max(1, (int) $rawArgs[$i + 1]);
            $i++;
            continue;
        }

        $parallel = min(4, (int) ($_SERVER['NUMBER_OF_PROCESSORS'] ?? 4));
        continue;
    }

    if (preg_match('/^(--parallel|-j)=(\d+)$/', $arg, $matches) === 1) {
        $parallel = max(1, (int) $matches[2]);
        continue;
    }

    if (preg_match('/^-j(\d+)$/', $arg, $matches) === 1) {
        $parallel = max(1, (int) $matches[1]);
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

$runner = new TestRunner();

if ($parallel > 1) {
    $exit = $runner->runParallel($paths, $parallel, verbose: $verbose);
} else {
    $exit = $runner->run($paths, verbose: $verbose);
}

exit($exit);
