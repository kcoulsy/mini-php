<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Models\User;
use Framework\Auth;
use Framework\Session;
use Tests\Support\DatabaseTestCase;

final class AuthRoleTest extends DatabaseTestCase
{
    public function testHomePathByRole(): void
    {
        $studentId = User::createWithRole('s@example.com', 'password123', User::ROLE_STUDENT);
        Auth::login($studentId);
        $this->assertEquals('/student', Auth::homePath());

        $teacherId = User::createWithRole('t@example.com', 'password123', User::ROLE_TEACHER);
        Auth::login($teacherId);
        $this->assertEquals('/teach', Auth::homePath());

        $adminId = User::createWithRole('a@example.com', 'password123', User::ROLE_ADMIN);
        Auth::login($adminId);
        $this->assertEquals('/admin', Auth::homePath());
    }

    public function testHasRole(): void
    {
        $id = User::createWithRole('t2@example.com', 'password123', User::ROLE_TEACHER);
        Auth::login($id);

        $this->assertTrue(Auth::hasRole(User::ROLE_TEACHER));
        $this->assertFalse(Auth::hasRole(User::ROLE_ADMIN));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        Session::start(['name' => 'test', 'lifetime' => 0]);
    }
}
