<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\User;
use Framework\App;
use Framework\Request;
use Framework\Response;
use Framework\Session;
use Framework\Testing\Concerns\InteractsWithHttp;
use Framework\Testing\TestCase;

/**
 * Full-stack tests: real router, controllers, views, in-memory SQLite.
 */
abstract class ApplicationTestCase extends TestCase
{
    use InteractsWithHttp;

    protected App $app;

    protected function setUp(): void
    {
        $this->app = createTestApplication();
        $_SESSION = [];
    }

    protected function dispatch(Request $request): Response
    {
        return $this->app->handle($request);
    }

    protected function actingAs(int $userId): void
    {
        $_SESSION[Session::USER_KEY] = $userId;
    }

    protected function createUser(
        string $email = 'user@example.com',
        string $password = 'password123',
        string $name = 'Test User',
    ): int {
        return User::create($email, $password, $name);
    }
}
