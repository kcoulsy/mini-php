<?php

declare(strict_types=1);

namespace Tests\App\Middleware;

use App\Middleware\RequireRole;
use App\Models\User;
use Framework\Auth;
use Framework\Request;
use Framework\Session;
use Tests\Support\DatabaseTestCase;

final class RequireRoleTest extends DatabaseTestCase
{
    public function testAllowsMatchingRole(): void
    {
        $user = $this->createUser('t@example.com', 'password123', '', User::ROLE_TEACHER);
        Auth::login($user->id);

        $middleware = RequireRole::allowing(User::ROLE_TEACHER, User::ROLE_ADMIN);
        $this->assertNull($middleware(Request::from('GET', '/teach')));
    }

    public function testForbiddenForWrongRole(): void
    {
        $user = $this->createUser('s@example.com', 'password123', '', User::ROLE_STUDENT);
        Auth::login($user->id);

        $middleware = RequireRole::allowing(User::ROLE_TEACHER);
        $response = $middleware(Request::from('GET', '/teach'));

        $this->assertNotNull($response);
        $this->assertEquals(403, $response->status());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        Session::start(['name' => 'test', 'lifetime' => 0]);
    }
}
