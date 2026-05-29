<?php

declare(strict_types=1);

namespace Framework\Model;

use Framework\Model\Attributes\AutoIncrement;
use Framework\Model\Attributes\BelongsTo;
use Framework\Model\Attributes\Column;
use Framework\Model\Attributes\HasMany;
use Framework\Model\Attributes\PrimaryKey;
use Framework\Model\Attributes\Table;
use ReflectionClass;
use ReflectionProperty;

final class ModelMetadata
{
    /** @var array<class-string, self> */
    private static array $cache = [];

    public readonly string $table;
    public readonly string $primaryKey;
    public readonly string $primaryKeyProperty;
    public readonly bool $autoIncrement;

    /** @var array<string, ColumnInfo> */
    public readonly array $columns;

    /** @var array<string, BelongsToInfo> */
    public readonly array $belongsTo;

    /** @var array<string, HasManyInfo> */
    public readonly array $hasMany;

    /**
     * @param class-string<Model> $class
     */
    public static function for(string $class): self
    {
        if (!isset(self::$cache[$class])) {
            self::$cache[$class] = new self($class);
        }

        return self::$cache[$class];
    }

    /**
     * @param class-string<Model> $class
     */
    private function __construct(private readonly string $class)
    {
        $reflection = new ReflectionClass($class);

        $tableAttr = $reflection->getAttributes(Table::class)[0] ?? null;
        if ($tableAttr === null) {
            throw new \InvalidArgumentException("Model {$class} requires #[Table]");
        }

        $this->table = $tableAttr->newInstance()->name;

        $columns = [];
        $primaryKey = null;
        $primaryKeyProperty = null;
        $autoIncrement = false;
        $belongsTo = [];
        $hasManyRaw = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();

            if ($property->getAttributes(BelongsTo::class) !== []) {
                $attr = $property->getAttributes(BelongsTo::class)[0]->newInstance();
                $belongsTo[$name] = new BelongsToInfo(
                    $name,
                    $attr->model,
                    $attr->foreignKey ?? self::snakeCase($name) . '_id',
                    $attr->ownerKey ?? 'id',
                );
                continue;
            }

            if ($property->getAttributes(HasMany::class) !== []) {
                $hasManyRaw[$name] = $property->getAttributes(HasMany::class)[0]->newInstance();
                continue;
            }

            if ($property->getAttributes(Column::class) === []
                && $property->getAttributes(PrimaryKey::class) === []) {
                continue;
            }

            $columnAttr = $property->getAttributes(Column::class)[0] ?? null;
            $columnName = $columnAttr !== null
                ? ($columnAttr->newInstance()->name ?? self::snakeCase($name))
                : self::snakeCase($name);

            $typeName = $property->getType();
            $phpType = $typeName !== null ? (string) $typeName : 'mixed';

            $columns[$name] = new ColumnInfo($name, $columnName, $phpType);

            if ($property->getAttributes(PrimaryKey::class) !== []) {
                $primaryKey = $columnName;
                $primaryKeyProperty = $name;
            }

            if ($property->getAttributes(AutoIncrement::class) !== []) {
                $autoIncrement = true;
            }
        }

        if ($primaryKey === null || $primaryKeyProperty === null) {
            throw new \InvalidArgumentException("Model {$class} requires a #[PrimaryKey] property");
        }

        $this->columns = $columns;
        $this->primaryKey = $primaryKey;
        $this->primaryKeyProperty = $primaryKeyProperty;
        $this->autoIncrement = $autoIncrement;
        $this->belongsTo = $belongsTo;

        $hasMany = [];
        foreach ($hasManyRaw as $name => $attr) {
            $relatedMeta = self::for($attr->model);
            $parentShort = $reflection->getShortName();
            $defaultForeign = self::snakeCase($parentShort) . '_id';

            $hasMany[$name] = new HasManyInfo(
                $name,
                $attr->model,
                $attr->foreignKey ?? $defaultForeign,
                $attr->localKey ?? $primaryKey,
            );
        }
        $this->hasMany = $hasMany;
    }

    public static function snakeCase(string $value): string
    {
        $value = preg_replace('/([a-z])([A-Z])/', '$1_$2', $value) ?? $value;

        return strtolower($value);
    }

    /**
     * @return class-string<Model>
     */
    public function modelClass(): string
    {
        return $this->class;
    }

    public function columnForProperty(string $property): ?string
    {
        return $this->columns[$property]->column ?? null;
    }

    /** @return list<string> */
    public function columnNames(): array
    {
        $names = [];
        foreach ($this->columns as $info) {
            $names[] = $info->column;
        }

        return $names;
    }
}

final class ColumnInfo
{
    public function __construct(
        public readonly string $property,
        public readonly string $column,
        public readonly string $phpType,
    ) {
    }
}

final class BelongsToInfo
{
    public function __construct(
        public readonly string $name,
        public readonly string $relatedClass,
        public readonly string $foreignKey,
        public readonly string $ownerKey,
    ) {
    }
}

final class HasManyInfo
{
    public function __construct(
        public readonly string $name,
        public readonly string $relatedClass,
        public readonly string $foreignKey,
        public readonly string $localKey,
    ) {
    }
}
