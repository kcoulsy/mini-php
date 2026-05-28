<?php

declare(strict_types=1);

namespace Tests\Framework\Testing;

use Framework\Testing\Assert;
use Framework\Testing\AssertionFailed;
use Framework\Testing\TestCase;

final class AssertTest extends TestCase
{
    protected function setUp(): void
    {
        Assert::resetCount();
    }

    public function testEqualsPassesForIdenticalValues(): void
    {
        Assert::equals('a', 'a');
        $this->assertEquals(1, Assert::assertionCount());
    }

    public function testEqualsThrowsAssertionFailed(): void
    {
        $threw = false;

        try {
            Assert::equals('expected', 'actual');
        } catch (AssertionFailed) {
            $threw = true;
        }

        $this->assertTrue($threw);
    }

    public function testInstanceOfValidatesObjectType(): void
    {
        Assert::instanceOf(\stdClass::class, new \stdClass());
        $this->assertTrue(true);
    }
}
