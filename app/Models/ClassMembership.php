<?php

declare(strict_types=1);

namespace App\Models;

use Framework\Database;

final class ClassMembership
{
    public static function isStudent(int $classId, int $userId): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT 1 FROM class_student WHERE class_id = :class_id AND user_id = :user_id LIMIT 1'
        );
        $stmt->execute(['class_id' => $classId, 'user_id' => $userId]);

        return $stmt->fetch() !== false;
    }

    public static function isTeacher(int $classId, int $userId): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT 1 FROM class_teacher WHERE class_id = :class_id AND user_id = :user_id LIMIT 1'
        );
        $stmt->execute(['class_id' => $classId, 'user_id' => $userId]);

        return $stmt->fetch() !== false;
    }

    public static function enrollStudent(int $classId, int $userId): bool
    {
        if (self::isStudent($classId, $userId)) {
            return true;
        }

        $stmt = Database::pdo()->prepare(
            'INSERT INTO class_student (class_id, user_id) VALUES (:class_id, :user_id)'
        );
        $stmt->execute(['class_id' => $classId, 'user_id' => $userId]);

        return true;
    }

    public static function unenrollStudent(int $classId, int $userId): bool
    {
        $stmt = Database::pdo()->prepare(
            'DELETE FROM class_student WHERE class_id = :class_id AND user_id = :user_id'
        );
        $stmt->execute(['class_id' => $classId, 'user_id' => $userId]);

        return $stmt->rowCount() > 0;
    }

    public static function assignTeacher(int $classId, int $userId): void
    {
        if (self::isTeacher($classId, $userId)) {
            return;
        }

        $stmt = Database::pdo()->prepare(
            'INSERT INTO class_teacher (class_id, user_id) VALUES (:class_id, :user_id)'
        );
        $stmt->execute(['class_id' => $classId, 'user_id' => $userId]);
    }

    public static function unassignTeacher(int $classId, int $userId): bool
    {
        $stmt = Database::pdo()->prepare(
            'DELETE FROM class_teacher WHERE class_id = :class_id AND user_id = :user_id'
        );
        $stmt->execute(['class_id' => $classId, 'user_id' => $userId]);

        return $stmt->rowCount() > 0;
    }

    /** @param list<int> $teacherIds */
    public static function syncTeachers(int $classId, array $teacherIds): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM class_teacher WHERE class_id = :class_id');
        $stmt->execute(['class_id' => $classId]);

        foreach ($teacherIds as $teacherId) {
            self::assignTeacher($classId, $teacherId);
        }
    }

    /** @return list<array{id: int|string, email: string, name: string, role: string}> */
    public static function studentsForClass(int $classId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT u.id, u.email, u.name, u.role FROM users u
             INNER JOIN class_student cs ON cs.user_id = u.id
             WHERE cs.class_id = :class_id
             ORDER BY u.name ASC, u.email ASC'
        );
        $stmt->execute(['class_id' => $classId]);

        return $stmt->fetchAll();
    }

    /** @return list<array{id: int|string, email: string, name: string, role: string}> */
    public static function teachersForClass(int $classId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT u.id, u.email, u.name, u.role FROM users u
             INNER JOIN class_teacher ct ON ct.user_id = u.id
             WHERE ct.class_id = :class_id
             ORDER BY u.name ASC, u.email ASC'
        );
        $stmt->execute(['class_id' => $classId]);

        return $stmt->fetchAll();
    }

    /** @return list<int> */
    public static function teacherIdsForClass(int $classId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT user_id FROM class_teacher WHERE class_id = :class_id'
        );
        $stmt->execute(['class_id' => $classId]);

        return array_map(static fn (array $row): int => (int) $row['user_id'], $stmt->fetchAll());
    }
}
