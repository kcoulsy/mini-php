<?php

declare(strict_types=1);

namespace Framework;

/**
 * Marks a string for automatic HTML escaping when echoed in a view.
 */
final class Escaped implements \Stringable
{
    public function __construct(private readonly string $value) {}

    public function __toString(): string
    {
        return View::e($this->value);
    }

    public function raw(): string
    {
        return $this->value;
    }
}
