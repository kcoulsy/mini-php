<?php

declare(strict_types=1);

namespace Tests\Framework;

use Framework\Auth;
use Framework\Session;
use Tests\Support\DatabaseTestCase;

final class AuthTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Session::start();
        $_SESSION = [];
    }

    public function testLoginSetsUserIdInSession(): void
    {
        $user = $this->createUser('auth@example.com', 'password123');

        Auth::login($user->id);

        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());
    }

    public function testLogoutClearsSessionUser(): void
    {
        $user = $this->createUser('logout@example.com', 'password123');
        Auth::login($user->id);

        Auth::logout();

        $this->assertFalse(Auth::check());
        $this->assertNull(Auth::id());
    }

    public function testAttemptSucceedsWithValidCredentials(): void
    {
        $user = $this->createUser('attempt@example.com', 'secret-pass');

        $this->assertTrue(Auth::attempt('attempt@example.com', 'secret-pass'));
        $this->assertEquals($user->id, Auth::id());
    }

    public function testAttemptFailsWithWrongPassword(): void
    {
        $this->createUser('fail@example.com', 'correct');

        $this->assertFalse(Auth::attempt('fail@example.com', 'wrong'));
        $this->assertFalse(Auth::check());
    }

    public function testUserReturnsRecordForLoggedInUser(): void
    {
        $user = $this->createUser('profile@example.com', 'password123', 'Profile');

        Auth::login($user->id);

        $sessionUser = Auth::user();

        $this->assertNotNull($sessionUser);
        $this->assertEquals('profile@example.com', $sessionUser->email);
        $this->assertEquals('Profile', $sessionUser->name);
    }
}
