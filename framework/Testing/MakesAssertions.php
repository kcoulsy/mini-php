<?php

declare(strict_types=1);

namespace Framework\Testing;

/**
 * Assertion helpers for test cases (explicit trait so IDEs index methods on subclasses).
 */
trait MakesAssertions
{
    protected function assertTrue(bool $condition, string $message = ''): void
    {
        Assert::true($condition, $message);
    }

    protected function assertFalse(bool $condition, string $message = ''): void
    {
        Assert::false($condition, $message);
    }

    protected function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        Assert::equals($expected, $actual, $message);
    }

    protected function assertNotNull(mixed $actual, string $message = ''): void
    {
        Assert::notNull($actual, $message);
    }

    protected function assertNull(mixed $actual, string $message = ''): void
    {
        Assert::null($actual, $message);
    }

    protected function assertCount(int $expected, countable|array $actual, string $message = ''): void
    {
        Assert::arrayCount($expected, $actual, $message);
    }

    protected function assertContains(string $needle, string $haystack, string $message = ''): void
    {
        Assert::contains($needle, $haystack, $message);
    }

    protected function assertMatches(string $pattern, string $value, string $message = ''): void
    {
        Assert::matches($pattern, $value, $message);
    }

    protected function assertInstanceOf(string $class, object $object, string $message = ''): void
    {
        Assert::instanceOf($class, $object, $message);
    }
}
