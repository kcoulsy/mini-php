<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Assignment;
use App\Models\ClassMembership;
use App\Models\SchoolClass;
use App\Models\User;
use Framework\App;
use Framework\Database;
use Framework\Request;
use Framework\Response;
use Framework\Session;
use Framework\Testing\Concerns\InteractsWithDatabase;
use Framework\Testing\Concerns\InteractsWithHttp;
use Framework\Testing\TestCase;

/**
 * Full-stack tests: real router, handlers, views, in-memory SQLite.
 */
abstract class ApplicationTestCase extends TestCase
{
    use InteractsWithDatabase;
    use InteractsWithHttp;

    protected App $app;

    private static ?App $sharedApp = null;

    public static function setUpBeforeClass(): void
    {
        self::$sharedApp = createTestApplication();
    }

    public static function tearDownAfterClass(): void
    {
        $_SESSION = [];
        self::$sharedApp = null;
    }

    protected function setUp(): void
    {
        if (self::$sharedApp === null) {
            self::$sharedApp = createTestApplication();
        }

        $this->app = self::$sharedApp;
        $_SESSION = [];
        $this->beginDatabaseTransaction();
    }

    protected function tearDown(): void
    {
        $this->rollbackDatabaseTransaction();
    }

    /** @return array{driver: string, path: string} */
    protected function databaseConfig(): array
    {
        /** @var array<string, mixed> $config */
        $config = require BASE_PATH . '/config/testing.php';

        /** @var array{driver: string, path: string} */
        return $config['database'];
    }

    protected function basePath(): string
    {
        return BASE_PATH;
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
        return User::create([
            'email' => mb_strtolower(trim($email)),
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'name' => trim($name),
            'role' => $role,
        ])->id();
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
        $schoolClass = SchoolClass::create([
            'name' => $name,
            'join_code' => SchoolClass::generateJoinCode(),
        ]);
        ClassMembership::assignTeacher($schoolClass->id, $teacherId);

        return [
            'id' => $schoolClass->id,
            'join_code' => $schoolClass->joinCode,
        ];
    }

    protected function createAssignmentForClass(int $classId, int $createdBy, string $title = 'Homework 1'): int
    {
        return Assignment::create([
            'class_id' => $classId,
            'title' => trim($title),
            'description' => 'Do the work.',
            'due_at' => null,
            'created_by' => $createdBy,
        ])->id();
    }
}
