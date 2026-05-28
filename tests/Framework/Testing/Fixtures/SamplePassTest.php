<?php

declare(strict_types=1);

namespace Tests\Framework\Testing\Fixtures;

use Framework\Testing\TestCase;

/** @internal Used by TestRunnerTest only */
final class SamplePassTest extends TestCase
{
    public function testPasses(): void
    {
        $this->assertTrue(true);
    }
}
