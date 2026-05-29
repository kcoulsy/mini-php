<?php

declare(strict_types=1);

namespace Framework\Model;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, Model>
 */
final class Collection implements Countable, IteratorAggregate
{
    /** @var list<Model> */
    private array $items;

    /**
     * @param list<Model> $items
     */
    public function __construct(array $items = [])
    {
        $this->items = array_values($items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /** @return list<Model> */
    public function all(): array
    {
        return $this->items;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function first(): ?Model
    {
        return $this->items[0] ?? null;
    }

    /**
     * @param list<Model> $parents
     */
    public static function make(array $parents): self
    {
        return new self($parents);
    }

    /**
     * Eager-load a relation on all models in the collection.
     */
    public function load(string $relation): void
    {
        if ($this->items === []) {
            return;
        }

        $this->items[0]->loadRelationOnMany($relation, $this->items);
    }
}
