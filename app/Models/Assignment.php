<?php

declare(strict_types=1);

namespace App\Models;

use Framework\Database;

/**
 * @phpstan-type AssignmentRow array{
 *     id: int|string,
 *     class_id: int|string,
 *     title: string,
 *     description: string,
 *     due_at: string|null,
 *     created_by: int|string,
 *     created_at: string,
 *     updated_at: string
 * }
 */
final class Assignment
{
    /** @return list<AssignmentRow> */
    public static function forClass(int $classId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM assignments WHERE class_id = :class_id ORDER BY id DESC'
        );
        $stmt->execute(['class_id' => $classId]);

        return $stmt->fetchAll();
    }

    /** @return list<AssignmentRow> */
    public static function all(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT * FROM assignments ORDER BY class_id ASC, id DESC'
        );

        return $stmt->fetchAll();
    }

    /** @return AssignmentRow|null */
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM assignments WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** @return AssignmentRow|null */
    public static function findForClass(int $id, int $classId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM assignments WHERE id = :id AND class_id = :class_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'class_id' => $classId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public static function create(
        int $classId,
        string $title,
        string $description,
        ?string $dueAt,
        int $createdBy,
    ): int {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO assignments (class_id, title, description, due_at, created_by)
             VALUES (:class_id, :title, :description, :due_at, :created_by)'
        );
        $stmt->execute([
            'class_id' => $classId,
            'title' => trim($title),
            'description' => trim($description),
            'due_at' => self::normalizeDueAt($dueAt),
            'created_by' => $createdBy,
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(
        int $id,
        int $classId,
        string $title,
        string $description,
        ?string $dueAt,
    ): bool {
        $stmt = Database::pdo()->prepare(
            'UPDATE assignments SET title = :title, description = :description, due_at = :due_at,
             updated_at = datetime(\'now\') WHERE id = :id AND class_id = :class_id'
        );
        $stmt->execute([
            'id' => $id,
            'class_id' => $classId,
            'title' => trim($title),
            'description' => trim($description),
            'due_at' => self::normalizeDueAt($dueAt),
        ]);

        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id, int $classId): bool
    {
        $stmt = Database::pdo()->prepare(
            'DELETE FROM assignments WHERE id = :id AND class_id = :class_id'
        );
        $stmt->execute(['id' => $id, 'class_id' => $classId]);

        return $stmt->rowCount() > 0;
    }

    public static function isPastDue(?string $dueAt): bool
    {
        if ($dueAt === null || $dueAt === '') {
            return false;
        }

        return strtotime($dueAt) < time();
    }

    public static function normalizeDueAt(?string $dueAt): ?string
    {
        if ($dueAt === null || trim($dueAt) === '') {
            return null;
        }

        return trim($dueAt);
    }
}
