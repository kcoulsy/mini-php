<?php

declare(strict_types=1);

namespace App;

use App\Models\Assignment;
use App\Models\ClassMembership;
use App\Models\Submission;
use App\Models\User;

final class Authorization
{
    public static function canAccessClass(int $userId, int $classId, ?User $user = null): bool
    {
        if (self::isAdmin($userId, $user)) {
            return true;
        }

        return ClassMembership::isStudent($classId, $userId)
            || ClassMembership::isTeacher($classId, $userId);
    }

    public static function canManageClass(int $userId, int $classId, ?User $user = null): bool
    {
        if (self::isAdmin($userId, $user)) {
            return true;
        }

        return ClassMembership::isTeacher($classId, $userId);
    }

    public static function canManageAssignment(int $userId, int $assignmentId, ?User $user = null): bool
    {
        if (self::isAdmin($userId, $user)) {
            return true;
        }

        $assignment = Assignment::find($assignmentId);

        if ($assignment === null) {
            return false;
        }

        return ClassMembership::isTeacher($assignment->classId, $userId);
    }

    public static function canViewSubmission(int $userId, int $submissionId, ?User $user = null): bool
    {
        if (self::isAdmin($userId, $user)) {
            return true;
        }

        $submission = Submission::find($submissionId);

        if ($submission === null) {
            return false;
        }

        if ($submission->studentId === $userId) {
            return true;
        }

        $assignment = Assignment::find($submission->assignmentId);

        if ($assignment === null) {
            return false;
        }

        return ClassMembership::isTeacher($assignment->classId, $userId);
    }

    public static function canEditSubmission(
        int $userId,
        int $submissionId,
        ?User $user = null,
        bool $adminOverride = true,
    ): bool {
        if ($adminOverride && self::isAdmin($userId, $user)) {
            return true;
        }

        $submission = Submission::find($submissionId);

        if ($submission === null || $submission->studentId !== $userId) {
            return false;
        }

        if ($submission->isGraded()) {
            return false;
        }

        $assignment = Assignment::find($submission->assignmentId);

        if ($assignment === null) {
            return false;
        }

        if ($assignment->isPastDue()) {
            return false;
        }

        return true;
    }

    public static function canEditSubmissionForAssignment(
        int $userId,
        int $assignmentId,
        ?User $user = null,
    ): bool {
        if (self::isAdmin($userId, $user)) {
            return true;
        }

        $assignment = Assignment::find($assignmentId);

        if ($assignment === null) {
            return false;
        }

        if (!ClassMembership::isStudent($assignment->classId, $userId)) {
            return false;
        }

        if ($assignment->isPastDue()) {
            return false;
        }

        $submission = Submission::query()
            ->where('assignment_id', $assignmentId)
            ->where('student_id', $userId)
            ->first();

        if ($submission instanceof Submission && $submission->isGraded()) {
            return false;
        }

        return true;
    }

    public static function canGradeSubmission(int $userId, int $submissionId, ?User $user = null): bool
    {
        if (self::isAdmin($userId, $user)) {
            return true;
        }

        $submission = Submission::find($submissionId);

        if ($submission === null) {
            return false;
        }

        $assignment = Assignment::find($submission->assignmentId);

        if ($assignment === null) {
            return false;
        }

        return ClassMembership::isTeacher($assignment->classId, $userId);
    }

    private static function isAdmin(int $userId, ?User $user): bool
    {
        if ($user !== null) {
            return $user->role === User::ROLE_ADMIN;
        }

        $row = User::find($userId);

        return $row !== null && $row->role === User::ROLE_ADMIN;
    }
}
