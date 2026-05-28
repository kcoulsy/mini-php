<?php

declare(strict_types=1);

namespace Tests\Unit;

use Framework\Database;
use Framework\Migrations\Migrator;
use Framework\Testing\TestCase;

final class MigratorTest extends TestCase
{
    private string $migrationsPath;

    protected function setUp(): void
    {
        parent::setUp();

        Database::disconnect();
        Database::connect(['driver' => 'sqlite', 'path' => ':memory:']);

        $this->migrationsPath = sys_get_temp_dir() . '/miniphp-migrations-' . uniqid('', true);
        mkdir($this->migrationsPath);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->migrationsPath . '/*.php') ?: []);
        rmdir($this->migrationsPath);

        parent::tearDown();
    }

    public function testUpRunsPendingMigrationsOnce(): void
    {
        file_put_contents(
            $this->migrationsPath . '/001_first.php',
            '<?php return static function (PDO $pdo): void { $pdo->exec("CREATE TABLE first (id INTEGER PRIMARY KEY)"); };',
        );
        file_put_contents(
            $this->migrationsPath . '/002_second.php',
            '<?php return static function (PDO $pdo): void { $pdo->exec("CREATE TABLE second (id INTEGER PRIMARY KEY)"); };',
        );

        $migrator = Migrator::fromPath(Database::pdo(), $this->migrationsPath);

        $firstRun = $migrator->up();
        $this->assertEquals(['001_first.php', '002_second.php'], $firstRun);
        $this->assertEquals(['001_first.php', '002_second.php'], $migrator->applied());
        $this->assertEquals([], $migrator->pending());

        $secondRun = $migrator->up();
        $this->assertEquals([], $secondRun);
    }
}
