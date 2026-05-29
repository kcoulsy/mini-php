<?php

declare(strict_types=1);

namespace Framework\Validation;

use Framework\Request;
use Framework\Response;

final class ValidationRedirect
{
    private const string ERRORS_KEY = 'validation.errors';

    private const string OLD_KEY = 'validation.old';

    public static function store(DtoResult $result): void
    {
        self::storeErrors($result->errors, $result->old);
    }

    /**
     * @param array<string, list<string>> $errors
     * @param array<string, mixed> $old
     */
    public static function storeErrors(array $errors, array $old = []): void
    {
        $_SESSION[self::ERRORS_KEY] = $errors;
        $_SESSION[self::OLD_KEY] = $old;
    }

    /** @param array<string, list<string>> $errors */
    public static function storeRequestErrors(Request $request, array $errors): void
    {
        self::storeErrors($errors, self::requestOld($request));
    }

    /** @return array<string, list<string>> */
    public static function pullErrors(): array
    {
        $errors = $_SESSION[self::ERRORS_KEY] ?? [];
        unset($_SESSION[self::ERRORS_KEY]);

        return is_array($errors) ? $errors : [];
    }

    /** @return array<string, mixed> */
    public static function pullOld(): array
    {
        $old = $_SESSION[self::OLD_KEY] ?? [];
        unset($_SESSION[self::OLD_KEY]);

        return is_array($old) ? $old : [];
    }

    public static function redirectBack(Request $request, DtoResult $result): Response
    {
        self::storeErrors($result->errors, array_merge(self::requestOld($request), $result->old));

        return Response::redirect(self::backUrl($request));
    }

    public static function backUrl(Request $request): string
    {
        $referer = $request->referer();

        if ($referer !== null && self::isSafePath($referer)) {
            return $referer;
        }

        $path = $request->path();

        if (preg_match('#^(/(?:admin|teach)/submissions/\d+)/grade$#', $path, $matches)) {
            return $matches[1];
        }

        if (preg_match('#^/admin/(?:assignments|classes|users)/\d+$#', $path)) {
            return $path . '/edit';
        }

        if (preg_match('#^/teach/classes/\d+/assignments/\d+$#', $path)) {
            return $path . '/edit';
        }

        if (preg_match('#^/teach/classes/\d+/assignments$#', $path)) {
            return $path . '/create';
        }

        return match ($path) {
            '/admin/assignments' => '/admin/assignments/create',
            '/admin/classes' => '/admin/classes/create',
            '/admin/users' => '/admin/users/create',
            '/student/classes/join' => '/student',
            default => $path,
        };
    }

    /** @return array<string, mixed> */
    private static function requestOld(Request $request): array
    {
        $old = [];

        foreach ($request->all() as $key => $value) {
            if (!is_string($key) || str_contains($key, 'assword') || $key === '_csrf') {
                continue;
            }

            $old[$key] = $value;
        }

        return $old;
    }

    private static function isSafePath(string $path): bool
    {
        return str_starts_with($path, '/')
            && !str_starts_with($path, '//')
            && !str_contains($path, "\0");
    }
}
