<?php

declare(strict_types=1);

namespace Framework\Routing\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Prefix
{
    public function __construct(public readonly string $path)
    {
    }
}
