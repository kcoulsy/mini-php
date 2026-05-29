<?php

declare(strict_types=1);

namespace Framework\Validation;

interface ValidationRule
{
    public function validate(mixed $value, object $dto, string $property): ?string;
}
