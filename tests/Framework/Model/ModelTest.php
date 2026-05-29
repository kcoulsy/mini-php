<?php

declare(strict_types=1);

namespace Tests\Framework\Model;

use Framework\Database;
use Tests\Framework\Model\Fixtures\TestAssignment;
use Tests\Framework\Model\Fixtures\TestSubmission;
use Tests\Framework\Model\Fixtures\TestUser;
use Tests\Support\DatabaseTestCase;

final class ModelTest extends DatabaseTestCase
{
    public function testFindAndCreate(): void
    {
        $user = TestUser::create([
            'email' => 'model@test.example',
            'password' => password_hash('secret', PASSWORD_DEFAULT),
            'name' => 'Model Test',
            'role' => 'student',
        ]);

        $this->assertTrue($user->id > 0);

        $found = TestUser::find($user->id);
        $this->assertNotNull($found);
        $this->assertEquals('model@test.example', $found->email);
    }

    public function testQueryBuilderWhereAndLatest(): void
    {
        TestUser::create([
            'email' => 'a@example.com',
            'password' => 'x',
            'name' => 'A',
            'role' => 'student',
        ]);
        TestUser::create([
            'email' => 'b@example.com',
            'password' => 'x',
            'name' => 'B',
            'role' => 'teacher',
        ]);

        $teachers = TestUser::query()->where('role', 'teacher')->get();
        $this->assertEquals(1, $teachers->count());
        $this->assertEquals('b@example.com', $teachers->first()?->email);
    }

    public function testLoadHasMany(): void
    {
        $teacherId = $this->seedTeacher();
        $classId = $this->seedClass();
        $assignmentId = $this->seedAssignment($classId, $teacherId);

        Database::pdo()->prepare(
            'INSERT INTO submissions (assignment_id, student_id, submitted_at)
             VALUES (:aid, :sid, datetime(\'now\'))'
        )->execute([
            'aid' => $assignmentId,
            'sid' => $this->seedStudent(),
        ]);

        $assignment = TestAssignment::find($assignmentId);
        $this->assertNotNull($assignment);

        $threw = false;
        try {
            $count = $assignment->submissions->count();
        } catch (\Error|\LogicException) {
            $threw = true;
        }
        $this->assertTrue($threw, 'Accessing unloaded relation should fail');

        $assignment->load('submissions');
        $this->assertEquals(1, $assignment->submissions->count());
    }

    public function testCollectionLoad(): void
    {
        $teacherId = $this->seedTeacher();
        $classId = $this->seedClass();
        $this->seedAssignment($classId, $teacherId);
        $this->seedAssignment($classId, $teacherId);

        $assignments = TestAssignment::query()->where('class_id', $classId)->get();
        $this->assertEquals(2, $assignments->count());

        $assignments->load('submissions');
        foreach ($assignments as $assignment) {
            $this->assertEquals(0, $assignment->submissions->count());
        }
    }

    private function seedTeacher(): int
    {
        return TestUser::create([
            'email' => 'teacher@example.com',
            'password' => 'hash',
            'name' => 'Teacher',
            'role' => 'teacher',
        ])->id();
    }

    private function seedStudent(): int
    {
        return TestUser::create([
            'email' => 'student@example.com',
            'password' => 'hash',
            'name' => 'Student',
            'role' => 'student',
        ])->id();
    }

    private function seedClass(): int
    {
        Database::pdo()->prepare(
            'INSERT INTO classes (name, join_code) VALUES (:name, :code)'
        )->execute(['name' => 'Math', 'code' => 'JOIN123']);

        return (int) Database::pdo()->lastInsertId();
    }

    private function seedAssignment(int $classId, int $teacherId): int
    {
        return TestAssignment::create([
            'class_id' => $classId,
            'title' => 'HW1',
            'description' => 'Do it',
            'due_at' => null,
            'created_by' => $teacherId,
        ])->id();
    }
}
