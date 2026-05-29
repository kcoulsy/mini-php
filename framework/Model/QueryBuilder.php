<?php

declare(strict_types=1);

namespace Framework\Model;

use Framework\Database;
use InvalidArgumentException;

/**
 * @template T of Model
 */
final class QueryBuilder
{
    private const ALLOWED_OPERATORS = ['=', '!=', '<>', '<', '>', '<=', '>=', 'LIKE'];

    /** @var list<array{type: string, column?: string, operator?: string, value?: mixed, values?: list<mixed>}> */
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
            $operator = $this->normalizeOperator((string) $operatorOrValue);
        }

        $meta = ModelMetadata::for($this->modelClass);
        $column = $this->resolveColumn($meta, $column);

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * @param list<mixed> $values
     */
    public function whereIn(string $column, array $values): self
    {
        $meta = ModelMetadata::for($this->modelClass);
        $column = $this->resolveColumn($meta, $column);

        if ($values === []) {
            $this->wheres[] = ['type' => 'never'];

            return $this;
        }

        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => array_values($values),
        ];

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
        [$whereSql, $params] = $this->buildWhereClause();
        $sql .= $whereSql;

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

    public function count(): int
    {
        $meta = ModelMetadata::for($this->modelClass);
        $sql = 'SELECT COUNT(*) FROM ' . $meta->table;
        [$whereSql, $params] = $this->buildWhereClause();
        $sql .= $whereSql;

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function delete(): int
    {
        $meta = ModelMetadata::for($this->modelClass);
        $sql = 'DELETE FROM ' . $meta->table;
        [$whereSql, $params] = $this->buildWhereClause();
        $sql .= $whereSql;

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(array $attributes): int
    {
        if ($attributes === []) {
            return 0;
        }

        $meta = ModelMetadata::for($this->modelClass);
        $sets = [];
        $params = [];

        foreach ($attributes as $key => $value) {
            $column = $this->resolveAttributeKey($meta, $key);
            if ($column === null) {
                continue;
            }

            $sets[] = $column . ' = :set_' . $column;
            $params['set_' . $column] = $value;
        }

        if ($sets === []) {
            return 0;
        }

        $sql = 'UPDATE ' . $meta->table . ' SET ' . implode(', ', $sets);
        [$whereSql, $whereParams] = $this->buildWhereClause();
        $sql .= $whereSql;

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([...$params, ...$whereParams]);

        return $stmt->rowCount();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function insert(array $attributes): void
    {
        if ($attributes === []) {
            throw new InvalidArgumentException('insert() requires at least one attribute.');
        }

        $meta = ModelMetadata::for($this->modelClass);
        $columns = [];
        $params = [];

        foreach ($attributes as $key => $value) {
            $column = $this->resolveAttributeKey($meta, $key);
            if ($column === null) {
                continue;
            }

            $columns[] = $column;
            $params[$column] = $value;
        }

        if ($columns === []) {
            throw new InvalidArgumentException('insert() requires at least one mapped attribute.');
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
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildWhereClause(): array
    {
        if ($this->wheres === []) {
            return ['', []];
        }

        $parts = [];
        $params = [];
        $paramIndex = 0;

        foreach ($this->wheres as $where) {
            if ($where['type'] === 'never') {
                $parts[] = '1 = 0';
                continue;
            }

            if ($where['type'] === 'in') {
                $inParams = [];
                foreach ($where['values'] as $value) {
                    $param = 'w' . $paramIndex++;
                    $inParams[] = ':' . $param;
                    $params[$param] = $value;
                }
                $parts[] = $where['column'] . ' IN (' . implode(', ', $inParams) . ')';
                continue;
            }

            $param = 'w' . $paramIndex++;
            $parts[] = $where['column'] . ' ' . $where['operator'] . ' :' . $param;
            $params[$param] = $where['value'];
        }

        return [' WHERE ' . implode(' AND ', $parts), $params];
    }

    private function normalizeOperator(string $operator): string
    {
        $operator = strtoupper(trim($operator));
        if ($operator === 'LIKE') {
            return 'LIKE';
        }

        if (!in_array($operator, self::ALLOWED_OPERATORS, true)) {
            throw new InvalidArgumentException('Unsupported where operator: ' . $operator);
        }

        return $operator;
    }

    private function resolveColumn(ModelMetadata $meta, string $column): string
    {
        if (isset($meta->columns[$column])) {
            return $meta->columns[$column]->column;
        }

        return $column;
    }

    private function resolveAttributeKey(ModelMetadata $meta, string $key): ?string
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
}
