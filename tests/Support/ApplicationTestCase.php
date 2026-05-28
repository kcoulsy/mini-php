<?php

declare(strict_types=1);

namespace Tests\Support;

use Framework\App;
use Framework\Request;
use Framework\Response;
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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
        $this->app = createTestApplication();
    }

    protected function dispatch(Request $request): Response
    {
        return $this->app->handle($request);
    }
}
