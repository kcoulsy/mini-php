<?php

declare(strict_types=1);

namespace Framework;

/**
 * Session-backed CSRF token generation and validation.
 */
final class Csrf
{
    public const string FIELD = '_csrf';

    public static function token(): string
    {
        self::ensureSession();

        $token = $_SESSION[Session::TOKEN_KEY] ?? null;

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $_SESSION[Session::TOKEN_KEY] = $token;
        }

        return $token;
    }

    public static function regenerate(): string
    {
        self::ensureSession();

        $token = bin2hex(random_bytes(32));
        $_SESSION[Session::TOKEN_KEY] = $token;

        return $token;
    }

    public static function field(): string
    {
        return View::csrfField();
    }

    public static function validate(Request $request): bool
    {
        self::ensureSession();

        $submitted = $request->input(self::FIELD);
        $expected = $_SESSION[Session::TOKEN_KEY] ?? null;

        if (!is_string($submitted) || !is_string($expected) || $expected === '') {
            return false;
        }

        return hash_equals($expected, $submitted);
    }

    private static function ensureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new \RuntimeException('Session must be started before using CSRF.');
        }
    }
}
