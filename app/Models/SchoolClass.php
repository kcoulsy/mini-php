<?php

declare(strict_types=1);

namespace App\Models;

use Framework\Database;

/**
 * @phpstan-type SchoolClassRow array{
 *     id: int|string,
 *     name: string,
 *     join_code: string,
 *     created_at: string,
 *     updated_at: string
 * }
 */
final class SchoolClass
{
    /** @return list<SchoolClassRow> */
    public static function all(): array
    {
        $stmt = Database::pdo()->query('SELECT * FROM classes ORDER BY name ASC');

        return $stmt->fetchAll();
    }

    /** @return list<SchoolClassRow> */
    public static function forStudent(int $studentId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT c.* FROM classes c
             INNER JOIN class_student cs ON cs.class_id = c.id
             WHERE cs.user_id = :user_id
             ORDER BY c.name ASC'
        );
        $stmt->execute(['user_id' => $studentId]);

        return $stmt->fetchAll();
    }

    /** @return list<SchoolClassRow> */
    public static function forTeacher(int $teacherId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT c.* FROM classes c
             INNER JOIN class_teacher ct ON ct.class_id = c.id
             WHERE ct.user_id = :user_id
             ORDER BY c.name ASC'
        );
        $stmt->execute(['user_id' => $teacherId]);

        return $stmt->fetchAll();
    }

    /** @return SchoolClassRow|null */
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM classes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** @return SchoolClassRow|null */
    public static function findByJoinCode(string $joinCode): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM classes WHERE join_code = :join_code LIMIT 1'
        );
        $stmt->execute(['join_code' => self::normalizeJoinCode($joinCode)]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public static function create(string $name, ?string $joinCode = null): int
    {
        $code = $joinCode !== null && $joinCode !== ''
            ? self::normalizeJoinCode($joinCode)
            : self::generateJoinCode();

        $stmt = Database::pdo()->prepare(
            'INSERT INTO classes (name, join_code) VALUES (:name, :join_code)'
        );
        $stmt->execute([
            'name' => trim($name),
            'join_code' => $code,
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, string $name, string $joinCode): bool
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE classes SET name = :name, join_code = :join_code, updated_at = datetime(\'now\')
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'name' => trim($name),
            'join_code' => self::normalizeJoinCode($joinCode),
        ]);

        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::pdo()->prepare('DELETE FROM classes WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public static function joinCodeExists(string $joinCode, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM classes WHERE join_code = :join_code';
        $params = ['join_code' => self::normalizeJoinCode($joinCode)];

        if ($exceptId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $exceptId;
        }

        $sql .= ' LIMIT 1';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() !== false;
    }

    public static function normalizeJoinCode(string $code): string
    {
        return strtoupper(trim($code));
    }

    public static function generateJoinCode(): string
    {
        do {
            $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        } while (self::joinCodeExists($code));

        return $code;
    }
}
