<?php

declare(strict_types=1);

namespace Framework\Testing;

abstract class TestCase
{
    protected function setUp(): void
    {
    }

    protected function tearDown(): void
    {
    }

    final public function runTest(string $method): void
    {
        $this->setUp();

        try {
            $this->{$method}();
        } finally {
            $this->tearDown();
        }
    }

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
