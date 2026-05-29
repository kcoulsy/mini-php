<?php

declare(strict_types=1);

namespace App\Models;

use Framework\Model\Collection;

final class ClassMembership
{
    public static function isStudent(int $classId, int $userId): bool
    {
        return ClassStudent::query()
            ->where('class_id', $classId)
            ->where('user_id', $userId)
            ->exists();
    }

    public static function isTeacher(int $classId, int $userId): bool
    {
        return ClassTeacher::query()
            ->where('class_id', $classId)
            ->where('user_id', $userId)
            ->exists();
    }

    /** @return Collection<int, SchoolClass> */
    public static function classesForStudent(int $studentId): Collection
    {
        $classIds = ClassStudent::query()
            ->where('user_id', $studentId)
            ->get()
            ->pluck('classId');

        if ($classIds === []) {
            return new Collection([]);
        }

        return SchoolClass::query()
            ->whereIn('id', $classIds)
            ->orderBy('name')
            ->get();
    }

    /** @return Collection<int, SchoolClass> */
    public static function classesForTeacher(int $teacherId): Collection
    {
        $classIds = ClassTeacher::query()
            ->where('user_id', $teacherId)
            ->get()
            ->pluck('classId');

        if ($classIds === []) {
            return new Collection([]);
        }

        return SchoolClass::query()
            ->whereIn('id', $classIds)
            ->orderBy('name')
            ->get();
    }

    public static function enrollStudent(int $classId, int $userId): bool
    {
        if (self::isStudent($classId, $userId)) {
            return true;
        }

        ClassStudent::query()->insert([
            'class_id' => $classId,
            'user_id' => $userId,
        ]);

        return true;
    }

    public static function unenrollStudent(int $classId, int $userId): bool
    {
        return ClassStudent::query()
            ->where('class_id', $classId)
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    public static function assignTeacher(int $classId, int $userId): void
    {
        if (self::isTeacher($classId, $userId)) {
            return;
        }

        ClassTeacher::query()->insert([
            'class_id' => $classId,
            'user_id' => $userId,
        ]);
    }

    public static function unassignTeacher(int $classId, int $userId): bool
    {
        return ClassTeacher::query()
            ->where('class_id', $classId)
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    /** @param list<int> $teacherIds */
    public static function syncTeachers(int $classId, array $teacherIds): void
    {
        ClassTeacher::query()
            ->where('class_id', $classId)
            ->delete();

        foreach ($teacherIds as $teacherId) {
            self::assignTeacher($classId, $teacherId);
        }
    }

    /** @return list<User> */
    public static function studentsForClass(int $classId): array
    {
        $userIds = ClassStudent::query()
            ->where('class_id', $classId)
            ->get()
            ->pluck('userId');

        if ($userIds === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', $userIds)
            ->orderBy('name')
            ->orderBy('email')
            ->get()
            ->all();
    }

    /** @return list<User> */
    public static function teachersForClass(int $classId): array
    {
        $userIds = ClassTeacher::query()
            ->where('class_id', $classId)
            ->get()
            ->pluck('userId');

        if ($userIds === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', $userIds)
            ->orderBy('name')
            ->orderBy('email')
            ->get()
            ->all();
    }

    /** @return list<int> */
    public static function teacherIdsForClass(int $classId): array
    {
        return array_map(
            static fn (mixed $id): int => (int) $id,
            ClassTeacher::query()
                ->where('class_id', $classId)
                ->get()
                ->pluck('userId'),
        );
    }
}
