<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\User;
use Framework\Database;
use Framework\Testing\Concerns\InteractsWithDatabase;
use Framework\Testing\TestCase;

/**
 * Model and repository tests — database only, no HTTP stack.
 */
abstract class DatabaseTestCase extends TestCase
{
    use InteractsWithDatabase;

    public static function setUpBeforeClass(): void
    {
        $instance = new static();
        Database::disconnect();
        Database::connect($instance->databaseConfig());
        require $instance->basePath() . '/database/migrate.php';
    }

    public static function tearDownAfterClass(): void
    {
    }

    protected function setUp(): void
    {
        $this->beginDatabaseTransaction();
    }

    protected function tearDown(): void
    {
        $this->rollbackDatabaseTransaction();
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

    protected function createUser(
        string $email = 'user@example.com',
        string $password = 'password123',
        string $name = '',
        string $role = User::ROLE_STUDENT,
    ): User {
        return User::create([
            'email' => mb_strtolower(trim($email)),
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'name' => trim($name),
            'role' => $role,
        ]);
    }
}
