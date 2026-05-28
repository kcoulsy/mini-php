<?php

declare(strict_types=1);

namespace Framework;

use App\Models\User;

/**
 * @phpstan-import-type UserRow from App\Models\User
 */
final class Auth
{
    public static function check(): bool
    {
        self::ensureSession();

        return isset($_SESSION[Session::USER_KEY]);
    }

    public static function id(): ?int
    {
        self::ensureSession();

        if (!isset($_SESSION[Session::USER_KEY])) {
            return null;
        }

        return (int) $_SESSION[Session::USER_KEY];
    }

    /** @return UserRow|null */
    public static function user(): ?array
    {
        $id = self::id();

        return $id === null ? null : User::find($id);
    }

    public static function login(int $userId): void
    {
        self::ensureSession();
        self::regenerateSessionId();
        $_SESSION[Session::USER_KEY] = $userId;
    }

    public static function logout(): void
    {
        self::ensureSession();
        unset($_SESSION[Session::USER_KEY]);
        self::regenerateSessionId();
    }

    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);

        if ($user === null || !password_verify($password, (string) $user['password'])) {
            return false;
        }

        self::login((int) $user['id']);

        return true;
    }

    public static function role(): ?string
    {
        $user = self::user();

        return $user === null ? null : (string) ($user['role'] ?? User::ROLE_STUDENT);
    }

    public static function isStudent(): bool
    {
        return self::role() === User::ROLE_STUDENT;
    }

    public static function isTeacher(): bool
    {
        return self::role() === User::ROLE_TEACHER;
    }

    public static function isAdmin(): bool
    {
        return self::role() === User::ROLE_ADMIN;
    }

    public static function homePath(): string
    {
        return match (self::role()) {
            User::ROLE_ADMIN => '/admin',
            User::ROLE_TEACHER => '/teach',
            default => '/student',
        };
    }

    /** @param list<string> $roles */
    public static function hasRole(string ...$roles): bool
    {
        $role = self::role();

        return $role !== null && in_array($role, $roles, true);
    }

    private static function ensureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new \RuntimeException('Session must be started before using Auth.');
        }
    }

    private static function regenerateSessionId(): void
    {
        if (!headers_sent()) {
            session_regenerate_id(true);
        }
    }
}
