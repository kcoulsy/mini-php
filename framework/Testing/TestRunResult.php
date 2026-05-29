<?php

declare(strict_types=1);

namespace Framework\Testing;

final class TestRunResult
{
    /** @param list<TestFileResult> $files */
    public function __construct(
        public int $passed = 0,
        public int $failed = 0,
        public int $assertions = 0,
        /** @var list<string> */
        public array $failures = [],
        public array $files = [],
    ) {
    }

    public function merge(self $other): self
    {
        return new self(
            passed: $this->passed + $other->passed,
            failed: $this->failed + $other->failed,
            assertions: $this->assertions + $other->assertions,
            failures: [...$this->failures, ...$other->failures],
            files: [...$this->files, ...$other->files],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'passed' => $this->passed,
            'failed' => $this->failed,
            'assertions' => $this->assertions,
            'failures' => $this->failures,
            'files' => array_map(static fn (TestFileResult $file): array => $file->toArray(), $this->files),
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        /** @var list<TestFileResult> $files */
        $files = array_map(
            static fn (array $file): TestFileResult => TestFileResult::fromArray($file),
            $data['files'] ?? [],
        );

        /** @var list<string> $failures */
        $failures = $data['failures'] ?? [];

        return new self(
            passed: (int) ($data['passed'] ?? 0),
            failed: (int) ($data['failed'] ?? 0),
            assertions: (int) ($data['assertions'] ?? 0),
            failures: $failures,
            files: $files,
        );
    }
}

final class TestFileResult
{
    /** @param list<TestMethodResult> $methods */
    public function __construct(
        public string $path,
        public array $methods = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'methods' => array_map(static fn (TestMethodResult $method): array => $method->toArray(), $this->methods),
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        /** @var list<TestMethodResult> $methods */
        $methods = array_map(
            static fn (array $method): TestMethodResult => TestMethodResult::fromArray($method),
            $data['methods'] ?? [],
        );

        return new self(
            path: (string) ($data['path'] ?? ''),
            methods: $methods,
        );
    }
}

final class TestMethodResult
{
    public function __construct(
        public string $name,
        public bool $passed,
        public int $assertions,
        public float $durationMs,
        public ?string $message = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'passed' => $this->passed,
            'assertions' => $this->assertions,
            'durationMs' => $this->durationMs,
            'message' => $this->message,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            passed: (bool) ($data['passed'] ?? false),
            assertions: (int) ($data['assertions'] ?? 0),
            durationMs: (float) ($data['durationMs'] ?? 0.0),
            message: isset($data['message']) ? (string) $data['message'] : null,
        );
    }
}
