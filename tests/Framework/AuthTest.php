<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Models\User;
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
        $userId = User::create('auth@example.com', 'password123');

        Auth::login($userId);

        $this->assertTrue(Auth::check());
        $this->assertEquals($userId, Auth::id());
    }

    public function testLogoutClearsSessionUser(): void
    {
        $userId = User::create('logout@example.com', 'password123');
        Auth::login($userId);

        Auth::logout();

        $this->assertFalse(Auth::check());
        $this->assertNull(Auth::id());
    }

    public function testAttemptSucceedsWithValidCredentials(): void
    {
        $userId = User::create('attempt@example.com', 'secret-pass');

        $this->assertTrue(Auth::attempt('attempt@example.com', 'secret-pass'));
        $this->assertEquals($userId, Auth::id());
    }

    public function testAttemptFailsWithWrongPassword(): void
    {
        User::create('fail@example.com', 'correct');

        $this->assertFalse(Auth::attempt('fail@example.com', 'wrong'));
        $this->assertFalse(Auth::check());
    }

    public function testUserReturnsRecordForLoggedInUser(): void
    {
        $userId = User::create('profile@example.com', 'password123', 'Profile');

        Auth::login($userId);

        $user = Auth::user();

        $this->assertNotNull($user);
        $this->assertEquals('profile@example.com', $user['email']);
        $this->assertEquals('Profile', $user['name']);
    }
}
