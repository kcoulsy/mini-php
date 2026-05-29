<?php

declare(strict_types=1);

namespace Framework\Testing;

abstract class TestCase
{
    use MakesAssertions;

    public static function setUpBeforeClass(): void
    {
    }

    public static function tearDownAfterClass(): void
    {
    }

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
}
