<?php

declare(strict_types=1);

namespace Framework;

/**
 * Default security response headers.
 */
final class HttpSecurity
{
    /**
     * @param array<string, mixed> $config
     */
    public static function headers(array $config = []): array
    {
        if (($config['headers'] ?? true) === false) {
            return [];
        }

        $headers = [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'X-XSS-Protection' => '0',
        ];

        $csp = $config['csp'] ?? "default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'";

        if (is_string($csp) && $csp !== '') {
            $headers['Content-Security-Policy'] = $csp;
        }

        if (($config['hsts'] ?? false) === true) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        return $headers;
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function apply(Response $response, array $config = []): Response
    {
        return $response->withHeaders(self::headers($config));
    }
}
