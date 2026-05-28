<?php

declare(strict_types=1);

namespace Tests\Framework;

use Framework\App;
use Framework\Csrf;
use Framework\Request;
use Framework\Response;
use Framework\Session;
use Framework\Testing\TestCase;

final class AppTest extends TestCase
{
    private App $app;

    protected function setUp(): void
    {
        /** @var array<string, mixed> $config */
        $config = require BASE_PATH . '/config/testing.php';

        $_SESSION = [];

        $this->app = new App($config);
        $this->app->router()->post('/echo', fn () => Response::html('ok'));
        $this->app->router()->get('/echo', fn () => Response::html('get'));
    }

    public function testGetRequestBypassesCsrf(): void
    {
        $response = $this->app->handle(Request::from('GET', '/echo'));

        $this->assertEquals(200, $response->status());
        $this->assertEquals('get', $response->body());
    }

    public function testPostWithoutCsrfReturns403(): void
    {
        $response = $this->app->handle(Request::from('POST', '/echo'));

        $this->assertEquals(403, $response->status());
        $this->assertContains('Invalid or missing CSRF token', $response->body());
    }

    public function testPostWithValidCsrfDispatchesRoute(): void
    {
        $token = Csrf::token();
        $response = $this->app->handle(Request::from('POST', '/echo', [], [
            Csrf::FIELD => $token,
        ]));

        $this->assertEquals(200, $response->status());
        $this->assertEquals('ok', $response->body());
    }

    public function testAppliesSecurityHeadersByDefault(): void
    {
        $response = $this->app->handle(Request::from('GET', '/echo'));

        $this->assertEquals('SAMEORIGIN', $response->header('X-Frame-Options'));
        $this->assertNotNull($response->header('Content-Security-Policy'));
    }

    public function testConfigReturnsNestedValues(): void
    {
        $this->assertEquals('sqlite', $this->app->config('database')['driver'] ?? null);
        $this->assertEquals('missing', $this->app->config('not.real', 'missing'));
    }
}
