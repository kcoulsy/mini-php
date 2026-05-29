<?php

declare(strict_types=1);

namespace Database\Seed;

use App\Models\Assignment;
use App\Models\ClassMembership;
use App\Models\ClassStudent;
use App\Models\ClassTeacher;
use App\Models\SchoolClass;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\User;

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

        $submission = self::ensureSubmission($mathHw1, $aliceId);
        $submission->grade(92.0, 'Strong work. Show your steps on #7.', $teacherId);

        self::ensureSubmission($englishEssay, $bobId);

        fwrite(STDOUT, "Demo seed complete.\n\n");
        self::printCredentials($mathId, $englishId);
    }

    public static function wipe(): void
    {
        $emails = self::demoEmails();

        SubmissionFile::query()->delete();
        Submission::query()->delete();
        Assignment::query()->delete();
        ClassStudent::query()->delete();
        ClassTeacher::query()->delete();
        SchoolClass::query()->whereIn('join_code', ['MATH101', 'ENG201'])->delete();
        User::query()->whereIn('email', $emails)->delete();

        fwrite(STDOUT, "Removed previous demo users, classes, and related data.\n");
    }

    private static function ensureUser(string $email, string $role, string $name): int
    {
        $existing = User::findBy('email', mb_strtolower(trim($email)));

        if ($existing !== null) {
            $existing->email = mb_strtolower(trim($email));
            $existing->name = trim($name);
            $existing->role = $role;
            $existing->password = password_hash(self::PASSWORD, PASSWORD_DEFAULT);
            $existing->save();

            return $existing->id;
        }

        return User::create([
            'email' => mb_strtolower(trim($email)),
            'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
            'name' => trim($name),
            'role' => $role,
        ])->id();
    }

    private static function ensureClass(string $name, string $joinCode): int
    {
        $existing = SchoolClass::findBy('join_code', strtoupper(trim($joinCode)));

        if ($existing instanceof SchoolClass) {
            $existing->update([
                'name' => trim($name),
                'join_code' => strtoupper(trim($joinCode)),
            ]);

            return $existing->id;
        }

        return SchoolClass::create([
            'name' => trim($name),
            'join_code' => strtoupper(trim($joinCode)),
        ])->id();
    }

    private static function ensureAssignment(
        int $classId,
        string $title,
        string $description,
        string $dueAt,
        int $createdBy,
    ): int {
        $existing = Assignment::query()
            ->where('class_id', $classId)
            ->where('title', $title)
            ->first();

        if ($existing instanceof Assignment) {
            $existing->update([
                'class_id' => $classId,
                'title' => trim($title),
                'description' => trim($description),
                'due_at' => trim($dueAt),
            ]);

            return $existing->id;
        }

        return Assignment::create([
            'class_id' => $classId,
            'title' => trim($title),
            'description' => trim($description),
            'due_at' => trim($dueAt),
            'created_by' => $createdBy,
        ])->id();
    }

    private static function ensureSubmission(int $assignmentId, int $studentId): Submission
    {
        $existing = Submission::query()
            ->where('assignment_id', $assignmentId)
            ->where('student_id', $studentId)
            ->first();

        if ($existing instanceof Submission) {
            return $existing;
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
            fwrite(STDOUT, "Math 101 join code: {$math->joinCode}\n");
        }

        if ($english !== null) {
            fwrite(STDOUT, "English 201 join code: {$english->joinCode}\n");
        }

        fwrite(STDOUT, "\nAlice has a graded submission on Math \"Problem set 1\".\n");
        fwrite(STDOUT, "Bob has an ungraded submission on English \"Essay draft\".\n");
    }
}
