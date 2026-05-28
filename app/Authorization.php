<?php

declare(strict_types=1);

namespace App;

use App\Models\Assignment;
use App\Models\ClassMembership;
use App\Models\Submission;
use App\Models\User;

final class Authorization
{
    public static function canAccessClass(int $userId, int $classId, ?array $user = null): bool
    {
        if (self::isAdmin($userId, $user)) {
            return true;
        }

        return ClassMembership::isStudent($classId, $userId)
            || ClassMembership::isTeacher($classId, $userId);
    }

    public static function canManageClass(int $userId, int $classId, ?array $user = null): bool
    {
        if (self::isAdmin($userId, $user)) {
            return true;
        }

        return ClassMembership::isTeacher($classId, $userId);
    }

    public static function canManageAssignment(int $userId, int $assignmentId, ?array $user = null): bool
    {
        if (self::isAdmin($userId, $user)) {
            return true;
        }

        $assignment = Assignment::find($assignmentId);

        if ($assignment === null) {
            return false;
        }

        return ClassMembership::isTeacher((int) $assignment['class_id'], $userId);
    }

    public static function canViewSubmission(int $userId, int $submissionId, ?array $user = null): bool
    {
        if (self::isAdmin($userId, $user)) {
            return true;
        }

        $submission = Submission::find($submissionId);

        if ($submission === null) {
            return false;
        }

        if ((int) $submission['student_id'] === $userId) {
            return true;
        }

        $assignment = Assignment::find((int) $submission['assignment_id']);

        if ($assignment === null) {
            return false;
        }

        return ClassMembership::isTeacher((int) $assignment['class_id'], $userId);
    }

    public static function canEditSubmission(
        int $userId,
        int $submissionId,
        ?array $user = null,
        bool $adminOverride = true,
    ): bool {
        if ($adminOverride && self::isAdmin($userId, $user)) {
            return true;
        }

        $submission = Submission::find($submissionId);

        if ($submission === null || (int) $submission['student_id'] !== $userId) {
            return false;
        }

        if (Submission::isGraded($submission)) {
            return false;
        }

        $assignment = Assignment::find((int) $submission['assignment_id']);

        if ($assignment === null) {
            return false;
        }

        if (Assignment::isPastDue($assignment['due_at'] !== null ? (string) $assignment['due_at'] : null)) {
            return false;
        }

        return true;
    }

    public static function canEditSubmissionForAssignment(
        int $userId,
        int $assignmentId,
        ?array $user = null,
    ): bool {
        if (self::isAdmin($userId, $user)) {
            return true;
        }

        $assignment = Assignment::find($assignmentId);

        if ($assignment === null) {
            return false;
        }

        if (!ClassMembership::isStudent((int) $assignment['class_id'], $userId)) {
            return false;
        }

        if (Assignment::isPastDue($assignment['due_at'] !== null ? (string) $assignment['due_at'] : null)) {
            return false;
        }

        $submission = Submission::findForStudent($assignmentId, $userId);

        if ($submission !== null && Submission::isGraded($submission)) {
            return false;
        }

        return true;
    }

    public static function canGradeSubmission(int $userId, int $submissionId, ?array $user = null): bool
    {
        if (self::isAdmin($userId, $user)) {
            return true;
        }

        $submission = Submission::find($submissionId);

        if ($submission === null) {
            return false;
        }

        $assignment = Assignment::find((int) $submission['assignment_id']);

        if ($assignment === null) {
            return false;
        }

        return ClassMembership::isTeacher((int) $assignment['class_id'], $userId);
    }

    private static function isAdmin(int $userId, ?array $user): bool
    {
        if ($user !== null) {
            return ($user['role'] ?? '') === User::ROLE_ADMIN;
        }

        $row = User::find($userId);

        return $row !== null && ($row['role'] ?? '') === User::ROLE_ADMIN;
    }
}
