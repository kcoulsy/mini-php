<?php

declare(strict_types=1);

namespace Framework\Model\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Table
{
    public function __construct(public readonly string $name)
    {
    }
}
