<?php

declare(strict_types=1);

namespace App\Validation;

use Attribute;
use Framework\Validation\ValidationRule;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class GradeScore implements ValidationRule
{
    public function validate(mixed $value, object $dto, string $property): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return 'Grade must be a number.';
        }

        $score = (float) $value;

        if ($score < 0 || $score > 100) {
            return 'Grade must be between 0 and 100.';
        }

        return null;
    }
}
