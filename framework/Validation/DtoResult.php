<?php

declare(strict_types=1);

namespace Framework\Validation;

final class DtoResult
{
    /**
     * @param array<string, list<string>> $errors
     * @param array<string, mixed> $old
     */
    public function __construct(
        public readonly ?object $dto,
        public readonly array $errors = [],
        public readonly array $old = [],
    ) {
    }

    public function passes(): bool
    {
        return $this->dto !== null;
    }

    public function fails(): bool
    {
        return !$this->passes();
    }
}
