<?php

declare(strict_types=1);

namespace Framework\Testing\Concerns;

use Framework\Database;

trait InteractsWithDatabase
{
    protected function refreshDatabase(): void
    {
        Database::disconnect();
        Database::connect($this->databaseConfig());
        require $this->basePath() . '/database/migrate.php';
    }

    /** @return array{driver: string, path: string} */
    abstract protected function databaseConfig(): array;

    abstract protected function basePath(): string;
}
