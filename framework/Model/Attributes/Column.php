<?php

declare(strict_types=1);

namespace Framework\Model\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Column
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $type = null,
    ) {
    }
}
