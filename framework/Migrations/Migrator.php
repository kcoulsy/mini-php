<?php

declare(strict_types=1);

namespace Framework\Migrations;

use PDO;
use RuntimeException;

final class Migrator
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $migrationsPath,
    ) {
    }

    public static function fromPath(PDO $pdo, string $migrationsPath): self
    {
        return new self($pdo, $migrationsPath);
    }

    /**
     * Run all pending migrations.
     *
     * @return list<string> Names of migrations that were applied.
     */
    public function up(): array
    {
        $this->ensureMigrationsTable();

        $ran = [];

        foreach ($this->pendingFiles() as $name => $path) {
            $this->runFile($name, $path);
            $ran[] = $name;
        }

        return $ran;
    }

    /** @return list<string> */
    public function applied(): array
    {
        $this->ensureMigrationsTable();

        $stmt = $this->pdo->query('SELECT migration FROM migrations ORDER BY migration');

        if ($stmt === false) {
            return [];
        }

        /** @var list<string> */
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'migration');
    }

    /** @return list<string> */
    public function pending(): array
    {
        return array_keys($this->pendingFiles());
    }

    private function ensureMigrationsTable(): void
    {
        $this->pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS migrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    migration TEXT NOT NULL UNIQUE,
    applied_at TEXT NOT NULL DEFAULT (datetime('now'))
)
SQL);
    }

    /**
     * @return array<string, string> migration filename => absolute path
     */
    private function pendingFiles(): array
    {
        $applied = array_flip($this->applied());
        $pending = [];

        foreach ($this->migrationFiles() as $name => $path) {
            if (!isset($applied[$name])) {
                $pending[$name] = $path;
            }
        }

        return $pending;
    }

    /**
     * @return array<string, string> migration filename => absolute path
     */
    private function migrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = glob($this->migrationsPath . '/*.php');

        if ($files === false) {
            return [];
        }

        sort($files, SORT_STRING);

        $map = [];

        foreach ($files as $path) {
            $map[basename($path)] = $path;
        }

        return $map;
    }

    private function runFile(string $name, string $path): void
    {
        /** @var mixed $migration */
        $migration = require $path;

        if (!is_callable($migration)) {
            throw new RuntimeException("Migration {$name} must return a callable that accepts PDO.");
        }

        $this->pdo->beginTransaction();

        try {
            $migration($this->pdo);

            $stmt = $this->pdo->prepare('INSERT INTO migrations (migration) VALUES (?)');
            $stmt->execute([$name]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw new RuntimeException("Migration {$name} failed: " . $e->getMessage(), 0, $e);
        }
    }
}
