<?php

declare(strict_types=1);

namespace App\Models;

use Framework\Database;

final class User
{
    public static function create(string $email, string $password, string $name = ''): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO users (email, password, name) VALUES (:email, :password, :name)'
        );
        $stmt->execute([
            'email' => self::normalizeEmail($email),
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'name' => trim($name),
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

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

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
