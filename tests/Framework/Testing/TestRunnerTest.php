<?php

declare(strict_types=1);

namespace Tests\Framework\Testing;

use Framework\Testing\TestRunner;
use Framework\Testing\TestCase;

final class TestRunnerTest extends TestCase
{
    public function testRunExecutesDiscoveredTestFile(): void
    {
        $file = BASE_PATH . '/tests/Framework/Testing/Fixtures/SamplePassTest.php';

        $exit = (new TestRunner())->run([$file], report: false, verbose: false);

        $this->assertEquals(0, $exit);
    }
}
