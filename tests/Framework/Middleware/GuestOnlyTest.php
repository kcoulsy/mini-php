<?php

declare(strict_types=1);

namespace Tests\Framework\Middleware;

use Framework\Auth;
use Framework\Middleware\GuestOnly;
use Framework\Request;
use Framework\Session;
use Framework\Testing\TestCase;

final class GuestOnlyTest extends TestCase
{
    protected function setUp(): void
    {
        Session::start();
        $_SESSION = [];
    }

    public function testAllowsGuests(): void
    {
        $middleware = new GuestOnly();
        $result = $middleware(Request::from('GET', '/login'));

        $this->assertNull($result);
    }

    public function testRedirectsAuthenticatedUsers(): void
    {
        Auth::login(1);

        $middleware = new GuestOnly();
        $result = $middleware(Request::from('GET', '/login'));

        $this->assertNotNull($result);
        $this->assertEquals(302, $result->status());
        $this->assertEquals('/items', $result->header('Location'));
    }
}
