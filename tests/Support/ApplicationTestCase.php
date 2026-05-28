<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Assignment;
use App\Models\ClassMembership;
use App\Models\SchoolClass;
use App\Models\User;
use Framework\App;
use Framework\Request;
use Framework\Response;
use Framework\Session;
use Framework\Testing\Concerns\InteractsWithHttp;
use Framework\Testing\TestCase;

/**
 * Full-stack tests: real router, controllers, views, in-memory SQLite.
 */
abstract class ApplicationTestCase extends TestCase
{
    use InteractsWithHttp;

    protected App $app;

    protected function setUp(): void
    {
        $this->app = createTestApplication();
        $_SESSION = [];
    }

    protected function dispatch(Request $request): Response
    {
        return $this->app->handle($request);
    }

    protected function actingAs(int $userId): void
    {
        $_SESSION[Session::USER_KEY] = $userId;
    }

    protected function createUser(
        string $email = 'user@example.com',
        string $password = 'password123',
        string $name = 'Test User',
        string $role = User::ROLE_STUDENT,
    ): int {
        return User::createWithRole($email, $password, $role, $name);
    }

    protected function createStudent(
        string $email = 'student@example.com',
        string $password = 'password123',
        string $name = 'Student',
    ): int {
        return $this->createUser($email, $password, $name, User::ROLE_STUDENT);
    }

    protected function createTeacher(
        string $email = 'teacher@example.com',
        string $password = 'password123',
        string $name = 'Teacher',
    ): int {
        return $this->createUser($email, $password, $name, User::ROLE_TEACHER);
    }

    protected function createAdmin(
        string $email = 'admin@example.com',
        string $password = 'password123',
        string $name = 'Admin',
    ): int {
        return $this->createUser($email, $password, $name, User::ROLE_ADMIN);
    }

    /** @return array{id: int, join_code: string} */
    protected function createClassWithTeacher(int $teacherId, string $name = 'Math 101'): array
    {
        $classId = SchoolClass::create($name);
        ClassMembership::assignTeacher($classId, $teacherId);

        $schoolClass = SchoolClass::find($classId);

        return [
            'id' => $classId,
            'join_code' => (string) ($schoolClass['join_code'] ?? ''),
        ];
    }

    protected function createAssignmentForClass(int $classId, int $createdBy, string $title = 'Homework 1'): int
    {
        return Assignment::create($classId, $title, 'Do the work.', null, $createdBy);
    }
}
