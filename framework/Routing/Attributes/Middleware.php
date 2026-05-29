<?php

declare(strict_types=1);

namespace Framework\Routing\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class Middleware
{
    /**
     * @param list<class-string|callable(\Framework\Request): ?\Framework\Response> $middleware
     */
    public function __construct(public readonly array $middleware)
    {
    }
}
