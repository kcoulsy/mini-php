<?php

declare(strict_types=1);

namespace Tests\App\Unit;

use App\Authorization;
use App\Models\ClassMembership;
use App\Models\SchoolClass;
use App\Models\User;
use Tests\Support\DatabaseTestCase;

final class AuthorizationTest extends DatabaseTestCase
{
    public function testCanManageClassForTeacher(): void
    {
        $teacher = $this->createUser('t@example.com', 'pass', '', User::ROLE_TEACHER);
        $schoolClass = SchoolClass::create(['name' => 'Science', 'join_code' => 'SCIENCE1']);
        ClassMembership::assignTeacher($schoolClass->id, $teacher->id);

        $this->assertTrue(Authorization::canManageClass($teacher->id, $schoolClass->id));
        $this->assertFalse(Authorization::canManageClass($teacher->id + 999, $schoolClass->id));
    }

    public function testAdminCanAccessClass(): void
    {
        $admin = $this->createUser('a@example.com', 'pass', '', User::ROLE_ADMIN);
        $schoolClass = SchoolClass::create(['name' => 'History', 'join_code' => 'HISTORY1']);

        $this->assertTrue(Authorization::canAccessClass($admin->id, $schoolClass->id));
    }
}
