<?php

declare(strict_types=1);

namespace Framework\Testing;

final class Assert
{
    private static int $count = 0;

    public static function resetCount(): void
    {
        self::$count = 0;
    }

    public static function count(): int
    {
        return self::$count;
    }

    public static function true(bool $condition, string $message = ''): void
    {
        self::record();

        if (!$condition) {
            self::fail($message !== '' ? $message : 'Expected true.');
        }
    }

    public static function false(bool $condition, string $message = ''): void
    {
        self::true(!$condition, $message !== '' ? $message : 'Expected false.');
    }

    public static function equals(mixed $expected, mixed $actual, string $message = ''): void
    {
        self::record();

        if ($expected !== $actual) {
            $detail = sprintf(
                "Expected %s\nGot      %s",
                self::export($expected),
                self::export($actual),
            );
            self::fail($message !== '' ? $message . "\n" . $detail : $detail);
        }
    }

    public static function contains(string $needle, string $haystack, string $message = ''): void
    {
        self::record();

        if (!str_contains($haystack, $needle)) {
            self::fail($message !== '' ? $message : "String does not contain [{$needle}].");
        }
    }

    public static function matches(string $pattern, string $value, string $message = ''): void
    {
        self::record();

        if (preg_match($pattern, $value) !== 1) {
            self::fail($message !== '' ? $message : "String does not match pattern [{$pattern}].");
        }
    }

    public static function instanceOf(string $class, object $object, string $message = ''): void
    {
        self::record();

        if (!$object instanceof $class) {
            self::fail($message !== '' ? $message : "Expected instance of {$class}.");
        }
    }

    public static function fail(string $message): void
    {
        throw new AssertionFailed($message);
    }

    private static function record(): void
    {
        self::$count++;
    }

    private static function export(mixed $value): string
    {
        return var_export($value, true);
    }
}
