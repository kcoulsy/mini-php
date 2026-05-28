<?php

declare(strict_types=1);

namespace Framework;

use App\Models\User;

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

    /** @return array<string, mixed>|null */
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
