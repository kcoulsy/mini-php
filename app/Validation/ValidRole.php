<?php

declare(strict_types=1);

namespace App\Validation;

use Attribute;
use Framework\Validation\ValidationRule;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class ValidRole implements ValidationRule
{
    public function validate(mixed $value, object $dto, string $property): ?string
    {
        if (!is_string($value) || !in_array($value, \App\Models\User::ROLES, true)) {
            return 'Invalid role.';
        }

        return null;
    }
}
