<?php

declare(strict_types=1);

namespace Framework\Routing\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class Post
{
    public function __construct(public readonly string $path)
    {
    }
}
