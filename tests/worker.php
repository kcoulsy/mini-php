<?php

declare(strict_types=1);

use Framework\Testing\TestRunner;

require __DIR__ . '/bootstrap.php';

/** @var list<string> $files */
$files = array_slice($argv, 1);

if ($files === []) {
    fwrite(STDERR, "No test files provided to worker.\n");
    exit(1);
}

$result = (new TestRunner())->runFiles($files, verbose: false, emitOutput: false);

echo json_encode($result->toArray(), JSON_THROW_ON_ERROR);

exit($result->failed > 0 ? 1 : 0);
