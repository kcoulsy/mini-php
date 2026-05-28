<?php

declare(strict_types=1);

namespace Framework;

/**
 * Session bootstrap with hardened cookie defaults.
 */
final class Session
{
    public const string TOKEN_KEY = '_csrf_token';

    public const string USER_KEY = '_user_id';

    public const string INTENDED_URL_KEY = 'url.intended';

    /**
     * @param array<string, mixed> $config
     */
    public static function start(array $config = []): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $name = (string) ($config['name'] ?? 'miniphp_sid');
        $lifetime = (int) ($config['lifetime'] ?? 0);
        $path = (string) ($config['path'] ?? '/');
        $domain = (string) ($config['domain'] ?? '');
        $secure = $config['secure'] ?? self::isHttps();
        $httponly = (bool) ($config['httponly'] ?? true);
        $samesite = (string) ($config['samesite'] ?? 'Lax');

        session_name($name);
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => $path,
            'domain' => $domain,
            'secure' => (bool) $secure,
            'httponly' => $httponly,
            'samesite' => $samesite,
        ]);

        session_start();
    }

    public static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        return isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443;
    }
}
