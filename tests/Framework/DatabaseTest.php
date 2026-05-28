<?php

declare(strict_types=1);

namespace Tests\Framework;

use Framework\Database;
use Framework\Testing\TestCase;

final class DatabaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Database::disconnect();
    }

    public function testConnectReturnsSingletonPdo(): void
    {
        Database::disconnect();

        $first = Database::connect(['driver' => 'sqlite', 'path' => ':memory:']);
        $second = Database::connect(['driver' => 'sqlite', 'path' => ':memory:']);

        $this->assertEquals($first, $second);
        $this->assertEquals($first, Database::pdo());
    }

    public function testPdoThrowsWhenNotConnected(): void
    {
        Database::disconnect();

        $threw = false;

        try {
            Database::pdo();
        } catch (\RuntimeException) {
            $threw = true;
        }

        $this->assertTrue($threw);
    }

    public function testDisconnectClearsConnection(): void
    {
        Database::connect(['driver' => 'sqlite', 'path' => ':memory:']);
        Database::disconnect();

        $this->assertTrue(true);
        $this->assertTrue((static function (): bool {
            try {
                Database::pdo();

                return false;
            } catch (\RuntimeException) {
                return true;
            }
        })());
    }
}
