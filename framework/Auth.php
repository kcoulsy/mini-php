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

    public static function user(): ?User
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
        unset($_SESSION[Session::USER_KEY], $_SESSION[Session::INTENDED_URL_KEY]);
        self::regenerateSessionId();
    }

    public static function pullIntendedUrl(): ?string
    {
        self::ensureSession();

        $url = $_SESSION[Session::INTENDED_URL_KEY] ?? null;
        unset($_SESSION[Session::INTENDED_URL_KEY]);

        if (!is_string($url) || !self::isSafePath($url)) {
            return null;
        }

        return $url;
    }

    public static function attempt(string $email, string $password): bool
    {
        $user = User::findBy('email', mb_strtolower(trim($email)));

        if ($user === null || !password_verify($password, $user->password)) {
            return false;
        }

        self::login($user->id);

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

    private static function isSafePath(string $path): bool
    {
        return str_starts_with($path, '/')
            && !str_starts_with($path, '//')
            && !str_contains($path, "\0");
    }
}
