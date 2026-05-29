<?php

declare(strict_types=1);

namespace Framework\Model\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class BelongsTo
{
    /**
     * @param class-string<\Framework\Model\Model> $model
     */
    public function __construct(
        public readonly string $model,
        public readonly ?string $foreignKey = null,
        public readonly ?string $ownerKey = null,
    ) {
    }
}
