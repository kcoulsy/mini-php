<?php

declare(strict_types=1);

namespace Tests\Support;

use Framework\Testing\Concerns\InteractsWithDatabase;
use Framework\Testing\TestCase;

/**
 * Model and repository tests — database only, no HTTP stack.
 */
abstract class DatabaseTestCase extends TestCase
{
    use InteractsWithDatabase;

    protected function setUp(): void
    {
        $this->refreshDatabase();
    }

    /** @return array{driver: string, path: string} */
    protected function databaseConfig(): array
    {
        /** @var array<string, mixed> $config */
        $config = require BASE_PATH . '/config/testing.php';

        /** @var array{driver: string, path: string} */
        return $config['database'];
    }

    protected function basePath(): string
    {
        return BASE_PATH;
    }
}
