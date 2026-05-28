<?php

declare(strict_types=1);

namespace Database\Seed;

use App\Models\Assignment;
use App\Models\ClassMembership;
use App\Models\SchoolClass;
use App\Models\Submission;
use App\Models\User;
use Framework\Database;

/**
 * Idempotent demo data for local development.
 * All accounts use the same password (see DemoSeeder::PASSWORD).
 */
final class DemoSeeder
{
    public const PASSWORD = 'password123';

    public static function run(bool $fresh = false): void
    {
        if ($fresh) {
            self::wipe();
        }

        $adminId = self::ensureUser('admin@demo.local', User::ROLE_ADMIN, 'Demo Admin');
        $teacherId = self::ensureUser('teacher@demo.local', User::ROLE_TEACHER, 'Taylor Teacher');
        $teacher2Id = self::ensureUser('teacher2@demo.local', User::ROLE_TEACHER, 'Jordan Teacher');
        $aliceId = self::ensureUser('alice@demo.local', User::ROLE_STUDENT, 'Alice Student');
        $bobId = self::ensureUser('bob@demo.local', User::ROLE_STUDENT, 'Bob Student');
        $carolId = self::ensureUser('carol@demo.local', User::ROLE_STUDENT, 'Carol Student');

        $mathId = self::ensureClass('Math 101', 'MATH101');
        $englishId = self::ensureClass('English 201', 'ENG201');

        ClassMembership::syncTeachers($mathId, [$teacherId]);
        ClassMembership::syncTeachers($englishId, [$teacher2Id]);

        ClassMembership::enrollStudent($mathId, $aliceId);
        ClassMembership::enrollStudent($mathId, $bobId);
        ClassMembership::enrollStudent($englishId, $bobId);
        ClassMembership::enrollStudent($englishId, $carolId);

        $mathHw1 = self::ensureAssignment(
            $mathId,
            'Problem set 1',
            'Complete exercises 1–10 from chapter 3.',
            self::dueInDays(14),
            $teacherId,
        );
        self::ensureAssignment(
            $mathId,
            'Problem set 2',
            'Review problems for the midterm.',
            self::dueInDays(30),
            $teacherId,
        );
        $englishEssay = self::ensureAssignment(
            $englishId,
            'Essay draft',
            'Submit a 500-word draft on the assigned reading.',
            self::dueInDays(7),
            $teacher2Id,
        );

        $submissionId = self::ensureSubmission($mathHw1, $aliceId);
        Submission::grade($submissionId, 92.0, 'Strong work. Show your steps on #7.', $teacherId);

        self::ensureSubmission($englishEssay, $bobId);

        fwrite(STDOUT, "Demo seed complete.\n\n");
        self::printCredentials($mathId, $englishId);
    }

    public static function wipe(): void
    {
        $pdo = Database::pdo();
        $emails = self::demoEmails();
        $placeholders = implode(',', array_fill(0, count($emails), '?'));

        $pdo->exec('DELETE FROM submission_files');
        $pdo->exec('DELETE FROM submissions');
        $pdo->exec('DELETE FROM assignments');
        $pdo->exec('DELETE FROM class_student');
        $pdo->exec('DELETE FROM class_teacher');
        $pdo->exec('DELETE FROM classes WHERE join_code IN (\'MATH101\', \'ENG201\')');

        $stmt = $pdo->prepare("DELETE FROM users WHERE email IN ({$placeholders})");
        $stmt->execute($emails);

        fwrite(STDOUT, "Removed previous demo users, classes, and related data.\n");
    }

    private static function ensureUser(string $email, string $role, string $name): int
    {
        $existing = User::findByEmail($email);

        if ($existing !== null) {
            User::update((int) $existing['id'], $email, $name, $role, self::PASSWORD);

            return (int) $existing['id'];
        }

        return User::createWithRole($email, self::PASSWORD, $role, $name);
    }

    private static function ensureClass(string $name, string $joinCode): int
    {
        $existing = SchoolClass::findByJoinCode($joinCode);

        if ($existing !== null) {
            SchoolClass::update((int) $existing['id'], $name, $joinCode);

            return (int) $existing['id'];
        }

        return SchoolClass::create($name, $joinCode);
    }

    private static function ensureAssignment(
        int $classId,
        string $title,
        string $description,
        string $dueAt,
        int $createdBy,
    ): int {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT id FROM assignments WHERE class_id = :class_id AND title = :title LIMIT 1'
        );
        $stmt->execute(['class_id' => $classId, 'title' => $title]);
        $row = $stmt->fetch();

        if ($row !== false) {
            $id = (int) $row['id'];
            Assignment::update($id, $classId, $title, $description, $dueAt);

            return $id;
        }

        return Assignment::create($classId, $title, $description, $dueAt, $createdBy);
    }

    private static function ensureSubmission(int $assignmentId, int $studentId): int
    {
        $existing = Submission::findForStudent($assignmentId, $studentId);

        if ($existing !== null) {
            return (int) $existing['id'];
        }

        return Submission::upsertForStudent($assignmentId, $studentId);
    }

    private static function dueInDays(int $days): string
    {
        return date('Y-m-d H:i:s', strtotime("+{$days} days"));
    }

    /** @return list<string> */
    private static function demoEmails(): array
    {
        return [
            'admin@demo.local',
            'teacher@demo.local',
            'teacher2@demo.local',
            'alice@demo.local',
            'bob@demo.local',
            'carol@demo.local',
        ];
    }

    private static function printCredentials(int $mathId, int $englishId): void
    {
        $math = SchoolClass::find($mathId);
        $english = SchoolClass::find($englishId);

        fwrite(STDOUT, "Password for all demo accounts: " . self::PASSWORD . "\n\n");
        fwrite(STDOUT, "Role     | Email              | Name\n");
        fwrite(STDOUT, "---------+--------------------+---------------\n");
        fwrite(STDOUT, "admin    | admin@demo.local   | Demo Admin\n");
        fwrite(STDOUT, "teacher  | teacher@demo.local | Taylor Teacher (Math 101)\n");
        fwrite(STDOUT, "teacher  | teacher2@demo.local| Jordan Teacher (English 201)\n");
        fwrite(STDOUT, "student  | alice@demo.local   | Alice (Math only)\n");
        fwrite(STDOUT, "student  | bob@demo.local     | Bob (both classes)\n");
        fwrite(STDOUT, "student  | carol@demo.local   | Carol (English only)\n\n");

        if ($math !== null) {
            fwrite(STDOUT, "Math 101 join code: {$math['join_code']}\n");
        }

        if ($english !== null) {
            fwrite(STDOUT, "English 201 join code: {$english['join_code']}\n");
        }

        fwrite(STDOUT, "\nAlice has a graded submission on Math \"Problem set 1\".\n");
        fwrite(STDOUT, "Bob has an ungraded submission on English \"Essay draft\".\n");
    }
}
