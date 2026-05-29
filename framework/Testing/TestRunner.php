<?php

declare(strict_types=1);

namespace Framework\Testing;

final class TestRunner
{
    private int $passed = 0;

    private int $failed = 0;

    private int $assertions = 0;

    private bool $verbose = true;

    /** @var list<string> */
    private array $failures = [];

    /**
     * @param list<string> $paths
     */
    public function run(array $paths, bool $report = true, bool $verbose = true): int
    {
        $this->verbose = $verbose;

        $files = $this->discover($paths);

        if ($this->verbose && $files !== []) {
            echo 'Running ' . count($files) . ' test file(s)...' . PHP_EOL . PHP_EOL;
        }

        foreach ($files as $file) {
            $this->runFile($file);
        }

        if ($report) {
            $this->report();
        }

        return $this->failed > 0 ? 1 : 0;
    }

    /**
     * @param list<string> $paths
     * @return list<string>
     */
    private function discover(array $paths): array
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

    private function runFile(string $file): void
    {
        require_once $file;

        $class = $this->classFromFile($file);

        if (!class_exists($class)) {
            $this->recordFailure("{$class} (from {$file})", 'Test class not found.');
            $this->logFailure("{$class} (from {$file})", 'Test class not found.', 0);

            return;
        }

        $test = new $class();

        if (!$test instanceof TestCase) {
            return;
        }

        $reflection = new \ReflectionClass($test);

        if ($reflection->isAbstract()) {
            return;
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
            return;
        }

        if ($this->verbose) {
            echo $this->relativeTestPath($file) . PHP_EOL;
        }

        foreach ($methods as $method) {
            $name = $class . '::' . $method->getName();

            try {
                Assert::resetCount();
                $test->runTest($method->getName());
                $count = Assert::assertionCount();
                $this->passed++;
                $this->assertions += $count;
                $this->logPass($name, $count);
            } catch (AssertionFailed $e) {
                $count = Assert::assertionCount();
                $this->failed++;
                $this->assertions += $count;
                $this->recordFailure($name, $e->getMessage());
                $this->logFailure($name, $e->getMessage(), $count);
            } catch (\Throwable $e) {
                $count = Assert::assertionCount();
                $this->failed++;
                $this->assertions += $count;
                $message = $e::class . ': ' . $e->getMessage();
                $this->recordFailure($name, $message);
                $this->logFailure($name, $message, $count);
            }
        }

        if ($this->verbose) {
            echo PHP_EOL;
        }
    }

    private function logPass(string $name, int $assertionCount): void
    {
        if ($this->verbose) {
            echo '  PASS  ' . $name . $this->assertionSuffix($assertionCount) . PHP_EOL;

            return;
        }

        echo '.';
    }

    private function logFailure(string $name, string $message, int $assertionCount): void
    {
        if ($this->verbose) {
            echo '  FAIL  ' . $name . $this->assertionSuffix($assertionCount) . PHP_EOL;
            echo $this->indent($message) . PHP_EOL;

            return;
        }

        echo str_contains($message, 'Assertion') ? 'F' : 'E';
    }

    private function assertionSuffix(int $count): string
    {
        if ($count === 0) {
            return '';
        }

        $label = $count === 1 ? 'assertion' : 'assertions';

        return " ({$count} {$label})";
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

    private function recordFailure(string $test, string $message): void
    {
        $this->failures[] = $test . "\n  " . str_replace("\n", "\n  ", $message);
    }

    private function report(): void
    {
        echo PHP_EOL;
        echo "Tests: {$this->passed} passed, {$this->failed} failed";
        echo " ({$this->assertions} assertions)" . PHP_EOL;

        if ($this->failures === [] || $this->verbose) {
            return;
        }

        echo PHP_EOL;

        foreach ($this->failures as $failure) {
            echo "FAIL  {$failure}" . PHP_EOL . PHP_EOL;
        }
    }
}
