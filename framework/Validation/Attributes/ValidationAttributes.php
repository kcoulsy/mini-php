<?php

declare(strict_types=1);

namespace Framework\Validation\Attributes;

use Attribute;
use Framework\Validation\ValidationRule;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Required implements ValidationRule
{
    public function validate(mixed $value, object $dto, string $property): ?string
    {
        if ($value === null || $value === '') {
            return 'This field is required.';
        }

        return null;
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Nullable
{
}

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Trim
{
}

#[Attribute(Attribute::TARGET_PROPERTY)]
final class StringType implements ValidationRule
{
    public function validate(mixed $value, object $dto, string $property): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            return 'This field must be a string.';
        }

        return null;
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
final class IntegerType implements ValidationRule
{
    public function validate(mixed $value, object $dto, string $property): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return null;
        }

        if (is_string($value) && ctype_digit($value)) {
            return null;
        }

        return 'This field must be an integer.';
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
final class BooleanType implements ValidationRule
{
    public function validate(mixed $value, object $dto, string $property): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value) || $value === '0' || $value === '1' || $value === 0 || $value === 1) {
            return null;
        }

        return 'This field must be a boolean.';
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Email implements ValidationRule
{
    public function validate(mixed $value, object $dto, string $property): ?string
    {
        $current = (string) ($value ?? '');

        if ($current === '' || filter_var($current, FILTER_VALIDATE_EMAIL) === false) {
            return 'A valid email is required.';
        }

        return null;
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Url implements ValidationRule
{
    public function validate(mixed $value, object $dto, string $property): ?string
    {
        $current = (string) ($value ?? '');

        if ($current === '') {
            return null;
        }

        if (filter_var($current, FILTER_VALIDATE_URL) === false) {
            return 'A valid URL is required.';
        }

        return null;
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
final class MinLength implements ValidationRule
{
    public function __construct(private readonly int $length)
    {
    }

    public function validate(mixed $value, object $dto, string $property): ?string
    {
        $current = (string) ($value ?? '');

        if ($current === '') {
            return null;
        }

        if (mb_strlen($current) < $this->length) {
            return "This field must be at least {$this->length} characters.";
        }

        return null;
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
final class MaxLength implements ValidationRule
{
    public function __construct(private readonly int $length)
    {
    }

    public function validate(mixed $value, object $dto, string $property): ?string
    {
        $current = (string) ($value ?? '');

        if (mb_strlen($current) > $this->length) {
            return "This field must be {$this->length} characters or fewer.";
        }

        return null;
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Min implements ValidationRule
{
    public function __construct(private readonly int|float $value)
    {
    }

    public function validate(mixed $value, object $dto, string $property): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return 'This field must be numeric.';
        }

        if ((float) $value < (float) $this->value) {
            return "This field must be at least {$this->value}.";
        }

        return null;
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Max implements ValidationRule
{
    public function __construct(private readonly int|float $value)
    {
    }

    public function validate(mixed $value, object $dto, string $property): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return 'This field must be numeric.';
        }

        if ((float) $value > (float) $this->value) {
            return "This field must be at most {$this->value}.";
        }

        return null;
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Confirmed implements ValidationRule
{
    public function validate(mixed $value, object $dto, string $property): ?string
    {
        $confirmationKey = $property . 'Confirmation';
        $confirmation = $dto->{$confirmationKey} ?? null;

        if ($value !== $confirmation) {
            return 'Confirmation does not match.';
        }

        return null;
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
final class ArrayType implements ValidationRule
{
    public function validate(mixed $value, object $dto, string $property): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            return 'This field must be an array.';
        }

        return null;
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Unique implements ValidationRule
{
    /**
     * @param class-string<\Framework\Model\Model> $model
     */
    public function __construct(
        private readonly string $model,
        private readonly ?string $column = null,
        private readonly mixed $except = null,
    ) {
    }

    public function validate(mixed $value, object $dto, string $property): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $meta = \Framework\Model\ModelMetadata::for($this->model);
        $column = $this->column ?? $meta->columnForProperty($property) ?? \Framework\Model\ModelMetadata::snakeCase($property);

        $sql = 'SELECT COUNT(*) FROM ' . $meta->table . ' WHERE ' . $column . ' = :value';
        $params = ['value' => $value];

        if ($this->except !== null) {
            $sql .= ' AND ' . $meta->primaryKey . ' != :except';
            $params['except'] = $this->except;
        }

        $stmt = \Framework\Database::pdo()->prepare($sql);
        $stmt->execute($params);

        if ((int) $stmt->fetchColumn() > 0) {
            return 'This value is already taken.';
        }

        return null;
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Exists implements ValidationRule
{
    /**
     * @param class-string<\Framework\Model\Model> $model
     */
    public function __construct(
        private readonly string $model,
        private readonly ?string $column = null,
    ) {
    }

    public function validate(mixed $value, object $dto, string $property): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $meta = \Framework\Model\ModelMetadata::for($this->model);
        $column = $this->column ?? $meta->primaryKey;

        $stmt = \Framework\Database::pdo()->prepare(
            'SELECT COUNT(*) FROM ' . $meta->table . ' WHERE ' . $column . ' = :value'
        );
        $stmt->execute(['value' => $value]);

        if ((int) $stmt->fetchColumn() === 0) {
            return 'The selected value is invalid.';
        }

        return null;
    }
}
