<?php

declare(strict_types=1);

namespace Tests\Framework\Testing;

use Framework\Testing\TestCase;
use Framework\Testing\TestRunResult;
use Framework\Testing\TestRunner;

final class TestRunnerTest extends TestCase
{
    public function testRunExecutesDiscoveredTestFile(): void
    {
        $file = BASE_PATH . '/tests/Framework/Testing/Fixtures/SamplePassTest.php';

        $exit = (new TestRunner())->run([$file], report: false, verbose: false);

        $this->assertEquals(0, $exit);
    }

    public function testRunIncludesTimingOutput(): void
    {
        $file = BASE_PATH . '/tests/Framework/Testing/Fixtures/SamplePassTest.php';

        ob_start();
        $exit = (new TestRunner())->run([$file], report: true, verbose: true);
        $output = (string) ob_get_clean();

        $this->assertEquals(0, $exit);
        $this->assertContains('Time:', $output);
        $this->assertContains(' ms)', $output);
    }

    public function testRunResultMergeAggregatesTotals(): void
    {
        $left = new TestRunResult(passed: 2, failed: 0, assertions: 3);
        $right = new TestRunResult(passed: 1, failed: 1, assertions: 4, failures: ['failure message']);
        $merged = $left->merge($right);

        $this->assertEquals(3, $merged->passed);
        $this->assertEquals(1, $merged->failed);
        $this->assertEquals(7, $merged->assertions);
        $this->assertEquals(['failure message'], $merged->failures);
    }
}
