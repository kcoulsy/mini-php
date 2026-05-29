<?php

declare(strict_types=1);

namespace Framework\Model;

use Framework\Database;

/**
 * @template T of Model
 */
final class QueryBuilder
{
    /** @var list<array{column: string, operator: string, value: mixed}> */
    private array $wheres = [];

    /** @var list<array{column: string, direction: string}> */
    private array $orders = [];

    private ?int $limit = null;

    /**
     * @param class-string<T> $modelClass
     */
    public function __construct(private readonly string $modelClass)
    {
    }

    public function where(string $column, mixed $operatorOrValue, mixed $value = null): self
    {
        if ($value === null) {
            $operator = '=';
            $value = $operatorOrValue;
        } else {
            $operator = (string) $operatorOrValue;
        }

        $meta = ModelMetadata::for($this->modelClass);
        $column = $this->resolveColumn($meta, $column);

        $this->wheres[] = ['column' => $column, 'operator' => $operator, 'value' => $value];

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $meta = ModelMetadata::for($this->modelClass);
        $this->orders[] = [
            'column' => $this->resolveColumn($meta, $column),
            'direction' => strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC',
        ];

        return $this;
    }

    public function latest(string $column = 'created_at'): self
    {
        return $this->orderBy($column, 'desc');
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @return Collection<int, T>
     */
    public function get(): Collection
    {
        $meta = ModelMetadata::for($this->modelClass);
        $sql = 'SELECT * FROM ' . $meta->table;
        $params = [];

        if ($this->wheres !== []) {
            $parts = [];
            foreach ($this->wheres as $i => $where) {
                $param = 'w' . $i;
                $parts[] = $where['column'] . ' ' . $where['operator'] . ' :' . $param;
                $params[$param] = $where['value'];
            }
            $sql .= ' WHERE ' . implode(' AND ', $parts);
        }

        if ($this->orders !== []) {
            $orderParts = [];
            foreach ($this->orders as $order) {
                $orderParts[] = $order['column'] . ' ' . $order['direction'];
            }
            $sql .= ' ORDER BY ' . implode(', ', $orderParts);
        } elseif ($meta->autoIncrement) {
            $sql .= ' ORDER BY ' . $meta->primaryKey . ' DESC';
        }

        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . (int) $this->limit;
        }

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $models = [];
        foreach ($rows as $row) {
            $models[] = ($this->modelClass)::newFromRow($row);
        }

        /** @var Collection<int, T> */
        return new Collection($models);
    }

    /**
     * @return T|null
     */
    public function first(): ?Model
    {
        $builder = clone $this;
        $builder->limit = 1;

        return $builder->get()->first();
    }

    private function resolveColumn(ModelMetadata $meta, string $column): string
    {
        if (isset($meta->columns[$column])) {
            return $meta->columns[$column]->column;
        }

        return $column;
    }
}
