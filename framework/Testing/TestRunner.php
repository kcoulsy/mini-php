<?php

declare(strict_types=1);

namespace Framework\Testing;

final class TestRunner
{
    private int $passed = 0;
    private int $failed = 0;
    private int $assertions = 0;

    /** @var list<string> */
    private array $failures = [];

    /**
     * @param list<string> $paths
     */
    public function run(array $paths): int
    {
        $files = $this->discover($paths);

        foreach ($files as $file) {
            $this->runFile($file);
        }

        $this->report();

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

                if (str_contains($fileInfo->getPathname(), DIRECTORY_SEPARATOR . 'Support' . DIRECTORY_SEPARATOR)) {
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

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() || !str_starts_with($method->getName(), 'test')) {
                continue;
            }

            if ($method->getDeclaringClass()->getName() !== $class) {
                continue;
            }

            $name = $class . '::' . $method->getName();

            try {
                Assert::resetCount();
                $test->runTest($method->getName());
                $this->passed++;
                $this->assertions += Assert::count();
                echo ".";
            } catch (AssertionFailed $e) {
                $this->failed++;
                $this->assertions += Assert::count();
                $this->recordFailure($name, $e->getMessage());
                echo "F";
            } catch (\Throwable $e) {
                $this->failed++;
                $this->assertions += Assert::count();
                $this->recordFailure($name, $e::class . ': ' . $e->getMessage());
                echo "E";
            }
        }
    }

    private function classFromFile(string $file): string
    {
        $normalized = str_replace('\\', '/', $file);
        $marker = '/tests/';

        if (!str_contains($normalized, $marker)) {
            return basename($file, '.php');
        }

        $relative = substr($normalized, (int) strpos($normalized, $marker) + strlen($marker));
        $relative = str_replace('/', '\\', $relative);

        return 'Tests\\' . substr($relative, 0, -4);
    }

    private function recordFailure(string $test, string $message): void
    {
        $this->failures[] = $test . "\n  " . str_replace("\n", "\n  ", $message);
    }

    private function report(): void
    {
        echo PHP_EOL . PHP_EOL;
        echo "Tests: {$this->passed} passed, {$this->failed} failed";
        echo " ({$this->assertions} assertions)" . PHP_EOL;

        if ($this->failures === []) {
            return;
        }

        echo PHP_EOL;

        foreach ($this->failures as $failure) {
            echo "FAIL  {$failure}" . PHP_EOL . PHP_EOL;
        }
    }
}
