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

    protected function beginDatabaseTransaction(): void
    {
        Database::pdo()->exec('BEGIN');
    }

    protected function rollbackDatabaseTransaction(): void
    {
        try {
            Database::pdo()->exec('ROLLBACK');
        } catch (\Throwable) {
            // Ignore when no transaction is open.
        }
    }

    /** @return array{driver: string, path: string} */
    abstract protected function databaseConfig(): array;

    abstract protected function basePath(): string;
}
