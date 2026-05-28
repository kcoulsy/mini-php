<?php

declare(strict_types=1);

namespace App\Models;

use Framework\Database;

/**
 * @phpstan-type UserRow array{
 *     id: int|string,
 *     email: string,
 *     password: string,
 *     name: string,
 *     role: string,
 *     created_at: string,
 *     updated_at: string
 * }
 */
final class User
{
    public const ROLE_STUDENT = 'student';
    public const ROLE_TEACHER = 'teacher';
    public const ROLE_ADMIN = 'admin';

    /** @var list<string> */
    public const ROLES = [self::ROLE_STUDENT, self::ROLE_TEACHER, self::ROLE_ADMIN];

    public static function createStudent(string $email, string $password, string $name = ''): int
    {
        return self::createWithRole($email, $password, self::ROLE_STUDENT, $name);
    }

    public static function createWithRole(
        string $email,
        string $password,
        string $role,
        string $name = '',
    ): int {
        if (!in_array($role, self::ROLES, true)) {
            throw new \InvalidArgumentException('Invalid role: ' . $role);
        }

        $stmt = Database::pdo()->prepare(
            'INSERT INTO users (email, password, name, role) VALUES (:email, :password, :name, :role)'
        );
        $stmt->execute([
            'email' => self::normalizeEmail($email),
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'name' => trim($name),
            'role' => $role,
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    /** @deprecated Use createStudent() */
    public static function create(string $email, string $password, string $name = ''): int
    {
        return self::createStudent($email, $password, $name);
    }

    /** @return list<UserRow> */
    public static function all(): array
    {
        $stmt = Database::pdo()->query('SELECT * FROM users ORDER BY id ASC');

        return $stmt->fetchAll();
    }

    /** @return UserRow|null */
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** @return UserRow|null */
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => self::normalizeEmail($email)]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public static function emailExists(string $email): bool
    {
        return self::findByEmail($email) !== null;
    }

    public static function update(
        int $id,
        string $email,
        string $name,
        string $role,
        ?string $password = null,
    ): bool {
        if (!in_array($role, self::ROLES, true)) {
            throw new \InvalidArgumentException('Invalid role: ' . $role);
        }

        if ($password !== null && $password !== '') {
            $stmt = Database::pdo()->prepare(
                'UPDATE users SET email = :email, name = :name, role = :role, password = :password,
                 updated_at = datetime(\'now\') WHERE id = :id'
            );
            $stmt->execute([
                'id' => $id,
                'email' => self::normalizeEmail($email),
                'name' => trim($name),
                'role' => $role,
                'password' => password_hash($password, PASSWORD_DEFAULT),
            ]);
        } else {
            $stmt = Database::pdo()->prepare(
                'UPDATE users SET email = :email, name = :name, role = :role,
                 updated_at = datetime(\'now\') WHERE id = :id'
            );
            $stmt->execute([
                'id' => $id,
                'email' => self::normalizeEmail($email),
                'name' => trim($name),
                'role' => $role,
            ]);
        }

        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::pdo()->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public static function countAdmins(): int
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM users WHERE role = :role');
        $stmt->execute(['role' => self::ROLE_ADMIN]);

        return (int) $stmt->fetchColumn();
    }

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
