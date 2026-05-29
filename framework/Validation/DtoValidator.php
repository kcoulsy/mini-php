<?php

declare(strict_types=1);

namespace Framework\Validation;

use Framework\Model\ModelMetadata;
use Framework\Request;
use Framework\Validation\Attributes\Nullable;
use Framework\Validation\Attributes\Trim;
use ReflectionClass;
use ReflectionProperty;

final class DtoMetadata
{
    /** @var array<class-string, self> */
    private static array $cache = [];

    /** @var list<PropertyInfo> */
    public readonly array $properties;

    /**
     * @param class-string $class
     */
    public static function for(string $class): self
    {
        if (!isset(self::$cache[$class])) {
            self::$cache[$class] = new self($class);
        }

        return self::$cache[$class];
    }

    /**
     * @param class-string $class
     */
    private function __construct(private readonly string $class)
    {
        $reflection = new ReflectionClass($class);
        $properties = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $rules = [];
            $nullable = false;
            $trim = false;

            foreach ($property->getAttributes() as $attribute) {
                $instance = $attribute->newInstance();

                if ($instance instanceof Nullable) {
                    $nullable = true;
                    continue;
                }

                if ($instance instanceof Trim) {
                    $trim = true;
                    continue;
                }

                if ($instance instanceof ValidationRule) {
                    $rules[] = $instance;
                }
            }

            $properties[] = new PropertyInfo(
                $property->getName(),
                self::inputKey($property->getName()),
                $property->getType()?->allowsNull() ?? false || $nullable,
                $trim,
                $rules,
            );
        }

        $this->properties = $properties;
    }

    /**
     * @return class-string
     */
    public function className(): string
    {
        return $this->class;
    }

    private static function inputKey(string $property): string
    {
        return ModelMetadata::snakeCase($property);
    }
}

final class PropertyInfo
{
    /**
     * @param list<ValidationRule> $rules
     */
    public function __construct(
        public readonly string $property,
        public readonly string $inputKey,
        public readonly bool $nullable,
        public readonly bool $trim,
        public readonly array $rules,
    ) {
    }
}

final class DtoValidator
{
    /**
     * @param class-string $dtoClass
     * @param array<string, mixed> $data
     */
    public static function validate(string $dtoClass, array $data): DtoResult
    {
        $meta = DtoMetadata::for($dtoClass);
        $errors = [];
        $values = [];

        foreach ($meta->properties as $info) {
            $raw = self::readInput($data, $info);
            $value = $raw;

            if ($info->trim && is_string($value)) {
                $value = trim($value);
            }

            $values[$info->property] = $value;
        }

        $dtoShell = (object) $values;

        foreach ($meta->properties as $info) {
            $value = $values[$info->property];

            if (($value === null || $value === '') && $info->nullable) {
                continue;
            }

            foreach ($info->rules as $rule) {
                $message = $rule->validate($value, $dtoShell, $info->property);

                if ($message !== null) {
                    $errors[$info->inputKey][] = $message;
                    break;
                }
            }
        }

        if ($errors !== []) {
            return new DtoResult(null, $errors, self::oldInput($meta, $data));
        }

        /** @var object $dto */
        $dto = new $dtoClass();
        foreach ($values as $property => $value) {
            if (($value === null || $value === '') && DtoMetadata::for($dtoClass)->properties) {
                $info = self::propertyInfo($meta, $property);
                if ($info !== null && $info->nullable) {
                    $dto->{$property} = null;
                    continue;
                }
            }

            $dto->{$property} = self::castProperty($value, $property, $dtoClass);
        }

        return new DtoResult($dto, [], self::oldInput($meta, $values));
    }

    private static function propertyInfo(DtoMetadata $meta, string $property): ?PropertyInfo
    {
        foreach ($meta->properties as $info) {
            if ($info->property === $property) {
                return $info;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function readInput(array $data, PropertyInfo $info): mixed
    {
        if (array_key_exists($info->property, $data)) {
            return $data[$info->property];
        }

        if (array_key_exists($info->inputKey, $data)) {
            return $data[$info->inputKey];
        }

        $confirmationKey = $info->inputKey . '_confirmation';
        if (str_ends_with($info->property, 'Confirmation') && array_key_exists($confirmationKey, $data)) {
            return $data[$confirmationKey];
        }

        if ($info->property === 'passwordConfirmation' && array_key_exists('password_confirmation', $data)) {
            return $data['password_confirmation'];
        }

        return null;
    }

    /**
     * @param class-string $dtoClass
     */
    private static function castProperty(mixed $value, string $property, string $dtoClass): mixed
    {
        $reflection = new ReflectionClass($dtoClass);
        $type = $reflection->getProperty($property)->getType();

        if ($type === null || $value === null || $value === '') {
            return $value;
        }

        $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;

        return match ($typeName) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOL),
            'string' => (string) $value,
            default => $value,
        };
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function oldInput(DtoMetadata $meta, array $data): array
    {
        $old = [];

        foreach ($meta->properties as $info) {
            if (str_contains($info->property, 'assword')) {
                continue;
            }

            $value = self::readInput($data, $info);
            if ($value !== null && $value !== '') {
                $old[$info->inputKey] = is_string($value) ? $value : (string) $value;
            }
        }

        return $old;
    }
}
