<?php

declare(strict_types=1);

namespace Tests\App\Unit;

use App\CurrentUser;
use App\Models\User;
use Framework\Auth;
use Framework\Session;
use Tests\Support\DatabaseTestCase;

final class CurrentUserTest extends DatabaseTestCase
{
    public function testHomePathByRole(): void
    {
        $student = $this->createUser('s@example.com', 'password123', '', User::ROLE_STUDENT);
        Auth::login($student->id);
        $this->assertEquals('/student', CurrentUser::homePath());

        $teacher = $this->createUser('t@example.com', 'password123', '', User::ROLE_TEACHER);
        Auth::login($teacher->id);
        $this->assertEquals('/teach', CurrentUser::homePath());

        $admin = $this->createUser('a@example.com', 'password123', '', User::ROLE_ADMIN);
        Auth::login($admin->id);
        $this->assertEquals('/admin', CurrentUser::homePath());
    }

    public function testHasRole(): void
    {
        $user = $this->createUser('t2@example.com', 'password123', '', User::ROLE_TEACHER);
        Auth::login($user->id);

        $this->assertTrue(CurrentUser::hasRole(User::ROLE_TEACHER));
        $this->assertFalse(CurrentUser::hasRole(User::ROLE_ADMIN));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        Session::start(['name' => 'test', 'lifetime' => 0]);
    }
}
