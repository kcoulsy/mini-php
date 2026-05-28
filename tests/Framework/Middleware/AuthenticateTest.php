<?php

declare(strict_types=1);

namespace Tests\Framework\Middleware;

use Framework\Auth;
use Framework\Middleware\Authenticate;
use Framework\Request;
use Framework\Session;
use Framework\Testing\TestCase;

final class AuthenticateTest extends TestCase
{
    protected function setUp(): void
    {
        Session::start();
        $_SESSION = [];
    }

    public function testAllowsAuthenticatedRequests(): void
    {
        Auth::login(1);

        $middleware = new Authenticate();
        $result = $middleware(Request::from('GET', '/items'));

        $this->assertNull($result);
    }

    public function testRedirectsGuestsAndStoresIntendedUrl(): void
    {
        $middleware = new Authenticate();
        $result = $middleware(Request::from('GET', '/items/create'));

        $this->assertNotNull($result);
        $this->assertEquals(302, $result->status());
        $this->assertEquals('/login', $result->header('Location'));
        $this->assertEquals('/items/create', $_SESSION[Session::INTENDED_URL_KEY]);
    }
}
