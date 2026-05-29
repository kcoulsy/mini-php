<?php

declare(strict_types=1);

namespace Framework\Model;

use Framework\Database;

abstract class Model
{
    /** @var array<string, bool> */
    private array $loadedRelations = [];

    /**
     * @param class-string<static> $class
     */
    public static function find(int|string $id): ?static
    {
        $meta = ModelMetadata::for(static::class);
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM ' . $meta->table . ' WHERE ' . $meta->primaryKey . ' = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : static::newFromRow($row);
    }

  public static function findBy(string $column, mixed $value): ?static
  {
    $meta = ModelMetadata::for(static::class);
    $resolved = $meta->columns[$column]->column ?? $column;

    $stmt = Database::pdo()->prepare(
      'SELECT * FROM ' . $meta->table . ' WHERE ' . $resolved . ' = :value LIMIT 1'
    );
    $stmt->execute(['value' => $value]);
    $row = $stmt->fetch();

    return $row === false ? null : static::newFromRow($row);
  }

    /**
     * @param array<string, mixed> $attributes keys: property or column names
     */
    public static function create(array $attributes): static
    {
        $meta = ModelMetadata::for(static::class);
        $columns = [];
        $params = [];

        foreach ($attributes as $key => $value) {
            $column = self::resolveAttributeKey($meta, $key);
            if ($column === null) {
                continue;
            }
            $columns[] = $column;
            $params[$column] = $value;
        }

        $placeholders = array_map(static fn (string $c): string => ':' . $c, $columns);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $meta->table,
            implode(', ', $columns),
            implode(', ', $placeholders),
        );

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        $id = (int) Database::pdo()->lastInsertId();

        return static::find($id) ?? throw new \RuntimeException('Failed to load model after create');
    }

    /**
     * @return QueryBuilder<static>
     */
    public static function query(): QueryBuilder
    {
        return new QueryBuilder(static::class);
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function newFromRow(array $row): static
    {
        $meta = ModelMetadata::for(static::class);
        /** @var static $instance */
        $instance = new static();

        foreach ($meta->columns as $property => $info) {
            if (!array_key_exists($info->column, $row)) {
                continue;
            }

            $value = $row[$info->column];
            $instance->{$property} = self::castValue($value, $info->phpType);
        }

        foreach ($meta->belongsTo as $name => $belongsInfo) {
            if (!array_key_exists($belongsInfo->foreignKey, $row)) {
                continue;
            }

            $fk = $row[$belongsInfo->foreignKey];
            if ($fk === null) {
                continue;
            }

            $related = $belongsInfo->relatedClass::find($fk);
            if ($related !== null) {
                $instance->{$name} = $related;
                $instance->loadedRelations[$name] = true;
            }
        }

        return $instance;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return Collection<int, static>
     */
    public static function collectionFromRows(array $rows): Collection
    {
        $items = [];

        foreach ($rows as $row) {
            $items[] = static::newFromRow($row);
        }

        return new Collection($items);
    }

    public function id(): int
    {
        $meta = ModelMetadata::for(static::class);
        $value = $this->{$meta->primaryKeyProperty};

        return (int) $value;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function fill(array $attributes): static
    {
        $meta = ModelMetadata::for(static::class);

        foreach ($attributes as $key => $value) {
            $property = self::resolvePropertyKey($meta, $key);
            if ($property !== null) {
                $this->{$property} = $value;
            }
        }

        return $this;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(array $attributes): bool
    {
        $this->fill($attributes);

        return $this->save();
    }

    public function save(): bool
    {
        $meta = ModelMetadata::for(static::class);
        $sets = [];
        $params = ['id' => $this->{$meta->primaryKeyProperty}];

        foreach ($meta->columns as $property => $info) {
            if ($property === $meta->primaryKeyProperty && $meta->autoIncrement) {
                continue;
            }

            $sets[] = $info->column . ' = :' . $info->column;
            $params[$info->column] = $this->{$property};
        }

        if (isset($this->updatedAt)) {
            // no-op; column handled if present on model
        }

        $hasUpdatedAt = isset($meta->columns['updatedAt']);
        if ($hasUpdatedAt) {
            $sets[] = 'updated_at = datetime(\'now\')';
        }

        $sql = 'UPDATE ' . $meta->table . ' SET ' . implode(', ', $sets)
            . ' WHERE ' . $meta->primaryKey . ' = :id';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    public function delete(): bool
    {
        $meta = ModelMetadata::for(static::class);
        $stmt = Database::pdo()->prepare(
            'DELETE FROM ' . $meta->table . ' WHERE ' . $meta->primaryKey . ' = :id'
        );
        $stmt->execute(['id' => $this->{$meta->primaryKeyProperty}]);

        return $stmt->rowCount() > 0;
    }

    public function load(string $relation): static
    {
        if ($this->loadedRelations[$relation] ?? false) {
            return $this;
        }

        $meta = ModelMetadata::for(static::class);

        if (isset($meta->hasMany[$relation])) {
            $this->loadHasMany($meta->hasMany[$relation]);

            return $this;
        }

        if (isset($meta->belongsTo[$relation])) {
            $this->loadBelongsTo($meta->belongsTo[$relation]);

            return $this;
        }

        throw new \InvalidArgumentException('Unknown relation: ' . $relation);
    }

    /**
     * @param list<Model> $models
     */
    public function loadRelationOnMany(string $relation, array $models): void
    {
        if ($models === []) {
            return;
        }

        $meta = ModelMetadata::for(static::class);

        if (isset($meta->hasMany[$relation])) {
            self::batchLoadHasMany($meta->hasMany[$relation], $models);

            return;
        }

        if (isset($meta->belongsTo[$relation])) {
            self::batchLoadBelongsTo($meta->belongsTo[$relation], $models);

            return;
        }

        throw new \InvalidArgumentException('Unknown relation: ' . $relation);
    }

    protected function ensureRelationLoaded(string $relation): void
    {
        if (!($this->loadedRelations[$relation] ?? false)) {
            throw new \LogicException(
                sprintf('Relation "%s" is not loaded. Call $model->load(\'%s\') first.', $relation, $relation)
            );
        }
    }

    private function loadHasMany(HasManyInfo $info): void
    {
        $relatedMeta = ModelMetadata::for($info->relatedClass);
        $localValue = $this->getAttributeValue($info->localKey);

        $stmt = Database::pdo()->prepare(
            'SELECT * FROM ' . $relatedMeta->table
            . ' WHERE ' . $info->foreignKey . ' = :fk'
            . ' ORDER BY ' . $relatedMeta->primaryKey . ' ASC'
        );
        $stmt->execute(['fk' => $localValue]);
        $rows = $stmt->fetchAll();

        $items = [];
        foreach ($rows as $row) {
            $items[] = $info->relatedClass::newFromRow($row);
        }

        $this->{$info->name} = new Collection($items);
        $this->loadedRelations[$info->name] = true;
    }

    private function loadBelongsTo(BelongsToInfo $info): void
    {
        $fk = $this->getAttributeValue($info->foreignKey);
        if ($fk === null) {
            throw new \LogicException('Cannot load belongs-to: foreign key is null');
        }

        $related = $info->relatedClass::find($fk);
        if ($related === null) {
            throw new \RuntimeException('Related model not found for ' . $info->name);
        }

        $this->{$info->name} = $related;
        $this->loadedRelations[$info->name] = true;
    }

    /**
     * @param list<Model> $parents
     */
    private static function batchLoadHasMany(HasManyInfo $info, array $parents): void
    {
        $parentMeta = ModelMetadata::for($parents[0]::class);
        $localKeyProperty = self::propertyForColumn($parentMeta, $info->localKey);
        $ids = [];
        foreach ($parents as $parent) {
            $ids[] = $parent->{$localKeyProperty};
        }
        $ids = array_values(array_unique($ids));

        if ($ids === []) {
            return;
        }

        $relatedMeta = ModelMetadata::for($info->relatedClass);
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM ' . $relatedMeta->table
            . ' WHERE ' . $info->foreignKey . ' IN (' . $placeholders . ')'
            . ' ORDER BY ' . $relatedMeta->primaryKey . ' ASC'
        );
        $stmt->execute($ids);
        $rows = $stmt->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $fk = $row[$info->foreignKey];
            $grouped[$fk][] = $info->relatedClass::newFromRow($row);
        }

        foreach ($parents as $parent) {
            $key = $parent->{$localKeyProperty};
            $items = $grouped[$key] ?? [];
            $parent->{$info->name} = new Collection($items);
            $parent->loadedRelations[$info->name] = true;
        }
    }

    /**
     * @param list<Model> $models
     */
    private static function batchLoadBelongsTo(BelongsToInfo $info, array $models): void
    {
        $fkProperty = self::propertyForColumn(ModelMetadata::for($models[0]::class), $info->foreignKey);
        $ids = [];
        foreach ($models as $model) {
            $ids[] = $model->{$fkProperty};
        }
        $ids = array_values(array_unique(array_filter($ids, static fn ($v) => $v !== null)));

        if ($ids === []) {
            return;
        }

        $relatedMeta = ModelMetadata::for($info->relatedClass);
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM ' . $relatedMeta->table
            . ' WHERE ' . $info->ownerKey . ' IN (' . $placeholders . ')'
        );
        $stmt->execute($ids);
        $rows = $stmt->fetchAll();

        $byId = [];
        foreach ($rows as $row) {
            $related = $info->relatedClass::newFromRow($row);
            $byId[$row[$info->ownerKey]] = $related;
        }

        foreach ($models as $model) {
            $fk = $model->{$fkProperty};
            $model->{$info->name} = $byId[$fk] ?? throw new \RuntimeException('Related model not found');
            $model->loadedRelations[$info->name] = true;
        }
    }

    private function getAttributeValue(string $column): mixed
    {
        $meta = ModelMetadata::for(static::class);
        $property = self::propertyForColumn($meta, $column);

        return $this->{$property};
    }

    private static function propertyForColumn(ModelMetadata $meta, string $column): string
    {
        if (isset($meta->columns[$column])) {
            return $column;
        }

        foreach ($meta->columns as $property => $info) {
            if ($info->column === $column) {
                return $property;
            }
        }

        if ($meta->primaryKey === $column) {
            return $meta->primaryKeyProperty;
        }

        throw new \InvalidArgumentException('Unknown column: ' . $column);
    }

    private static function resolveAttributeKey(ModelMetadata $meta, string $key): ?string
    {
        if (isset($meta->columns[$key])) {
            return $meta->columns[$key]->column;
        }

        foreach ($meta->columns as $info) {
            if ($info->column === $key) {
                return $info->column;
            }
        }

        return null;
    }

    private static function resolvePropertyKey(ModelMetadata $meta, string $key): ?string
    {
        if (isset($meta->columns[$key])) {
            return $key;
        }

        foreach ($meta->columns as $property => $info) {
            if ($info->column === $key) {
                return $property;
            }
        }

        return null;
    }

    private static function castValue(mixed $value, string $phpType): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($phpType) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => (bool) $value,
            'string' => (string) $value,
            default => $value,
        };
    }
}
