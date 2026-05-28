<?php

declare(strict_types=1);

namespace Tests\App\Unit;

use App\Authorization;
use App\Models\ClassMembership;
use App\Models\User;
use Tests\Support\DatabaseTestCase;

final class AuthorizationTest extends DatabaseTestCase
{
    public function testCanManageClassForTeacher(): void
    {
        $teacherId = User::createWithRole('t@example.com', 'pass', User::ROLE_TEACHER);
        $classId = \App\Models\SchoolClass::create('Science');
        ClassMembership::assignTeacher($classId, $teacherId);

        $this->assertTrue(Authorization::canManageClass($teacherId, $classId));
        $this->assertFalse(Authorization::canManageClass($teacherId + 999, $classId));
    }

    public function testAdminCanAccessClass(): void
    {
        $adminId = User::createWithRole('a@example.com', 'pass', User::ROLE_ADMIN);
        $classId = \App\Models\SchoolClass::create('History');

        $this->assertTrue(Authorization::canAccessClass($adminId, $classId));
    }
}
