<?php

declare(strict_types=1);

namespace Framework\Testing;

final class TestRunner
{
    private bool $verbose = true;

    private bool $emitOutput = true;

    private float $elapsedSeconds = 0.0;

    /**
     * @param list<string> $paths
     */
    public function run(array $paths, bool $report = true, bool $verbose = true): int
    {
        $this->verbose = $verbose;
        $this->emitOutput = $report;

        $startedAt = hrtime(true);
        $files = $this->discover($paths);

        if ($this->emitOutput && $this->verbose && $files !== []) {
            echo 'Running ' . count($files) . ' test file(s)...' . PHP_EOL . PHP_EOL;
        }

        $result = $this->runFiles($files, verbose: $verbose, emitOutput: false);

        $this->elapsedSeconds = (hrtime(true) - $startedAt) / 1_000_000_000;

        if ($report) {
            $this->printResult($result, $this->elapsedSeconds);
        }

        return $result->failed > 0 ? 1 : 0;
    }

    /**
     * @param list<string> $paths
     */
    public function runParallel(array $paths, int $workers, bool $verbose = true): int
    {
        $this->verbose = $verbose;
        $startedAt = hrtime(true);
        $files = $this->discover($paths);

        if ($workers < 2) {
            return $this->run($paths, report: true, verbose: $verbose);
        }

        if ($verbose && $files !== []) {
            echo 'Running ' . count($files) . ' test file(s) with ' . $workers . ' workers...' . PHP_EOL . PHP_EOL;
        }

        $buckets = $this->splitFiles($files, $workers);
        $merged = new TestRunResult();

        foreach ($this->runWorkerBuckets($buckets) as $result) {
            $merged = $merged->merge($result);
        }

        usort($merged->files, static fn (TestFileResult $a, TestFileResult $b): int => strcmp($a->path, $b->path));

        $elapsedSeconds = (hrtime(true) - $startedAt) / 1_000_000_000;
        $this->printResult($merged, $elapsedSeconds);

        return $merged->failed > 0 ? 1 : 0;
    }

    /**
     * @param list<string> $files
     * @return list<list<string>>
     */
    private function splitFiles(array $files, int $workers): array
    {
        $buckets = array_fill(0, $workers, []);

        foreach ($files as $index => $file) {
            $buckets[$index % $workers][] = $file;
        }

        /** @var list<list<string>> */
        return array_values(array_filter($buckets, static fn (array $bucket): bool => $bucket !== []));
    }

    /**
     * @param list<list<string>> $buckets
     * @return list<TestRunResult>
     */
    private function runWorkerBuckets(array $buckets): array
    {
        $workerScript = $this->workerScriptPath();
        $phpBinary = PHP_BINARY;
        $processes = [];
        $results = [];

        foreach ($buckets as $index => $files) {
            $command = escapeshellarg($phpBinary) . ' ' . escapeshellarg($workerScript);

            foreach ($files as $file) {
                $command .= ' ' . escapeshellarg($file);
            }

            $pipes = [];
            $process = proc_open(
                $command,
                [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ],
                $pipes,
                dirname($workerScript),
            );

            if (!is_resource($process)) {
                throw new \RuntimeException('Failed to start parallel test worker ' . ($index + 1) . '.');
            }

            fclose($pipes[0]);
            $processes[] = [
                'process' => $process,
                'stdout' => $pipes[1],
                'stderr' => $pipes[2],
            ];
        }

        foreach ($processes as $worker) {
            $stdout = stream_get_contents($worker['stdout']);
            $stderr = stream_get_contents($worker['stderr']);
            fclose($worker['stdout']);
            fclose($worker['stderr']);

            $exitCode = proc_close($worker['process']);

            if ($stdout === false || trim($stdout) === '') {
                $message = trim($stderr !== false && $stderr !== '' ? $stderr : '');
                throw new \RuntimeException(
                    'Parallel test worker returned no results.'
                    . ($message !== '' ? ' ' . $message : ''),
                );
            }

            try {
                /** @var array<string, mixed> $payload */
                $payload = json_decode(trim($stdout), true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                throw new \RuntimeException('Parallel test worker returned invalid JSON: ' . trim($stdout));
            }

            $results[] = TestRunResult::fromArray($payload);

            if ($exitCode !== 0 && ($payload['failed'] ?? 0) === 0) {
                $message = trim($stderr !== false && $stderr !== '' ? $stderr : (string) $stdout);
                throw new \RuntimeException('Parallel test worker failed: ' . ($message !== '' ? $message : 'unknown error'));
            }
        }

        return $results;
    }

    private function workerScriptPath(): string
    {
        if (!defined('BASE_PATH')) {
            throw new \RuntimeException('BASE_PATH is not defined.');
        }

        return BASE_PATH . '/tests/worker.php';
    }

    /**
     * @param list<string> $files
     */
    public function runFiles(array $files, bool $verbose = true, bool $emitOutput = false): TestRunResult
    {
        $previousVerbose = $this->verbose;
        $previousEmit = $this->emitOutput;

        $this->verbose = $verbose;
        $this->emitOutput = $emitOutput;

        $result = new TestRunResult();

        foreach ($files as $file) {
            $fileResult = $this->runFile($file);
            $result = $result->merge($fileResult);
        }

        $this->verbose = $previousVerbose;
        $this->emitOutput = $previousEmit;

        return $result;
    }

    public function printResult(TestRunResult $result, ?float $elapsedSeconds = null): void
    {
        foreach ($result->files as $fileResult) {
            $this->printFileResult($fileResult);
        }

        echo PHP_EOL;
        echo "Tests: {$result->passed} passed, {$result->failed} failed";
        echo " ({$result->assertions} assertions)" . PHP_EOL;

        $seconds = $elapsedSeconds ?? $this->elapsedSeconds;

        if ($seconds > 0) {
            echo 'Time: ' . $this->formatSeconds($seconds) . PHP_EOL;
        }

        if ($result->failures === [] || $this->verbose) {
            return;
        }

        echo PHP_EOL;

        foreach ($result->failures as $failure) {
            echo "FAIL  {$failure}" . PHP_EOL . PHP_EOL;
        }
    }

    /**
     * @param list<string> $paths
     * @return list<string>
     */
    public function discover(array $paths): array
    {
        $files = [];

        foreach ($paths as $path) {
            if (is_file($path) && str_ends_with($path, 'Test.php')) {
                $files[] = $path;
                continue;
            }

            if (!is_dir($path)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            );

            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile() || !str_ends_with($fileInfo->getFilename(), 'Test.php')) {
                    continue;
                }

                $pathname = $fileInfo->getPathname();

                if (
                    str_contains($pathname, DIRECTORY_SEPARATOR . 'Support' . DIRECTORY_SEPARATOR)
                    || str_contains($pathname, DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR)
                ) {
                    continue;
                }

                $files[] = $fileInfo->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    private function runFile(string $file): TestRunResult
    {
        require_once $file;

        $class = $this->classFromFile($file);
        $fileResult = new TestFileResult($this->relativeTestPath($file));
        $result = new TestRunResult(files: [$fileResult]);

        if (!class_exists($class)) {
            $message = 'Test class not found.';
            $result->failed++;
            $result->failures[] = "{$class} (from {$file})\n  {$message}";
            $fileResult->methods[] = new TestMethodResult(
                name: "{$class} (from {$file})",
                passed: false,
                assertions: 0,
                durationMs: 0.0,
                message: $message,
            );
            $this->logMethodResult($fileResult->methods[0]);

            return $result;
        }

        $test = new $class();

        if (!$test instanceof TestCase) {
            return new TestRunResult();
        }

        $reflection = new \ReflectionClass($test);

        if ($reflection->isAbstract()) {
            return new TestRunResult();
        }

        $methods = [];

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() || !str_starts_with($method->getName(), 'test')) {
                continue;
            }

            if ($method->getDeclaringClass()->getName() !== $class) {
                continue;
            }

            $methods[] = $method;
        }

        if ($methods === []) {
            return new TestRunResult();
        }

        if ($this->emitOutput && $this->verbose) {
            echo $fileResult->path . PHP_EOL;
        }

        $class::setUpBeforeClass();

        $fileDurationMs = 0.0;

        try {
            foreach ($methods as $method) {
                $name = $class . '::' . $method->getName();
                $startedAt = hrtime(true);

                try {
                    Assert::resetCount();
                    $test->runTest($method->getName());
                    $count = Assert::assertionCount();
                    $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
                    $fileDurationMs += $durationMs;

                    $methodResult = new TestMethodResult(
                        name: $name,
                        passed: true,
                        assertions: $count,
                        durationMs: $durationMs,
                    );
                    $fileResult->methods[] = $methodResult;
                    $result->passed++;
                    $result->assertions += $count;
                    $this->logMethodResult($methodResult);
                } catch (AssertionFailed $e) {
                    $count = Assert::assertionCount();
                    $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
                    $fileDurationMs += $durationMs;

                    $methodResult = new TestMethodResult(
                        name: $name,
                        passed: false,
                        assertions: $count,
                        durationMs: $durationMs,
                        message: $e->getMessage(),
                    );
                    $fileResult->methods[] = $methodResult;
                    $result->failed++;
                    $result->assertions += $count;
                    $result->failures[] = $name . "\n  " . str_replace("\n", "\n  ", $e->getMessage());
                    $this->logMethodResult($methodResult);
                } catch (\Throwable $e) {
                    $count = Assert::assertionCount();
                    $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
                    $fileDurationMs += $durationMs;
                    $message = $e::class . ': ' . $e->getMessage();

                    $methodResult = new TestMethodResult(
                        name: $name,
                        passed: false,
                        assertions: $count,
                        durationMs: $durationMs,
                        message: $message,
                    );
                    $fileResult->methods[] = $methodResult;
                    $result->failed++;
                    $result->assertions += $count;
                    $result->failures[] = $name . "\n  " . str_replace("\n", "\n  ", $message);
                    $this->logMethodResult($methodResult);
                }
            }
        } finally {
            $class::tearDownAfterClass();
        }

        if ($this->emitOutput && $this->verbose) {
            $testCount = count($fileResult->methods);
            $testLabel = $testCount === 1 ? 'test' : 'tests';
            echo '  (' . $testCount . ' ' . $testLabel . ', ' . $this->formatMilliseconds($fileDurationMs) . ')' . PHP_EOL;
            echo PHP_EOL;
        }

        return $result;
    }

    private function logMethodResult(TestMethodResult $methodResult): void
    {
        if (!$this->emitOutput) {
            return;
        }

        if ($methodResult->passed) {
            $this->logPass($methodResult);

            return;
        }

        $this->logFailure($methodResult);
    }

    private function logPass(TestMethodResult $methodResult): void
    {
        if ($this->verbose) {
            echo '  PASS  ' . $methodResult->name
                . $this->resultSuffix($methodResult)
                . PHP_EOL;

            return;
        }

        echo '.';
    }

    private function logFailure(TestMethodResult $methodResult): void
    {
        if ($this->verbose) {
            echo '  FAIL  ' . $methodResult->name
                . $this->resultSuffix($methodResult)
                . PHP_EOL;

            if ($methodResult->message !== null) {
                echo $this->indent($methodResult->message) . PHP_EOL;
            }

            return;
        }

        $message = $methodResult->message ?? '';
        echo str_contains($message, 'Assertion') ? 'F' : 'E';
    }

    private function resultSuffix(TestMethodResult $methodResult): string
    {
        $parts = [];

        if ($methodResult->assertions > 0) {
            $label = $methodResult->assertions === 1 ? 'assertion' : 'assertions';
            $parts[] = "{$methodResult->assertions} {$label}";
        }

        $parts[] = $this->formatMilliseconds($methodResult->durationMs);

        return ' (' . implode(', ', $parts) . ')';
    }

    private function formatMilliseconds(float $milliseconds): string
    {
        if ($milliseconds >= 100) {
            return number_format($milliseconds, 0) . ' ms';
        }

        if ($milliseconds >= 10) {
            return number_format($milliseconds, 1) . ' ms';
        }

        return number_format($milliseconds, 2) . ' ms';
    }

    private function formatSeconds(float $seconds): string
    {
        if ($seconds >= 10) {
            return number_format($seconds, 2) . 's';
        }

        return number_format($seconds, 3) . 's';
    }

    private function printFileResult(TestFileResult $fileResult): void
    {
        if (!$this->emitOutput || !$this->verbose || $fileResult->methods === []) {
            return;
        }

        echo $fileResult->path . PHP_EOL;

        $fileDurationMs = 0.0;

        foreach ($fileResult->methods as $methodResult) {
            $fileDurationMs += $methodResult->durationMs;

            if ($methodResult->passed) {
                echo '  PASS  ' . $methodResult->name . $this->resultSuffix($methodResult) . PHP_EOL;
                continue;
            }

            echo '  FAIL  ' . $methodResult->name . $this->resultSuffix($methodResult) . PHP_EOL;

            if ($methodResult->message !== null) {
                echo $this->indent($methodResult->message) . PHP_EOL;
            }
        }

        $testCount = count($fileResult->methods);
        $testLabel = $testCount === 1 ? 'test' : 'tests';
        echo '  (' . $testCount . ' ' . $testLabel . ', ' . $this->formatMilliseconds($fileDurationMs) . ')' . PHP_EOL;
        echo PHP_EOL;
    }

    private function indent(string $message): string
    {
        return '        ' . str_replace("\n", PHP_EOL . '        ', trim($message));
    }

    private function relativeTestPath(string $file): string
    {
        if (!defined('BASE_PATH')) {
            return $file;
        }

        $base = str_replace('\\', '/', BASE_PATH) . '/';
        $normalized = str_replace('\\', '/', $file);

        if (str_starts_with($normalized, $base)) {
            return substr($normalized, strlen($base));
        }

        return $file;
    }

    private function classFromFile(string $file): string
    {
        $normalized = str_replace('\\', '/', $file);
        $markers = ['/tests/', 'tests/'];

        foreach ($markers as $marker) {
            $pos = strrpos($normalized, $marker);

            if ($pos === false) {
                continue;
            }

            $relative = substr($normalized, $pos + strlen($marker));
            $relative = str_replace('/', '\\', $relative);

            return 'Tests\\' . substr($relative, 0, -4);
        }

        return basename($file, '.php');
    }
}
