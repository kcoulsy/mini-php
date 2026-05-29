<?php

declare(strict_types=1);

namespace Tests\App\Middleware;

use App\Middleware\GuestOnly;
use Framework\Auth;
use Framework\Request;
use Framework\Session;
use Tests\Support\DatabaseTestCase;

final class GuestOnlyTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Session::start(['name' => 'test', 'lifetime' => 0]);
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
        $user = $this->createUser('guest@example.com', 'password123');
        Auth::login($user->id);

        $middleware = new GuestOnly();
        $result = $middleware(Request::from('GET', '/login'));

        $this->assertNotNull($result);
        $this->assertEquals(302, $result->status());
        $this->assertEquals('/student', $result->header('Location'));
    }
}
