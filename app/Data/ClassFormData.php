<?php

declare(strict_types=1);

namespace App\Data;

use Framework\Validation\Attributes\MaxLength;
use Framework\Validation\Attributes\Nullable;
use Framework\Validation\Attributes\Required;
use Framework\Validation\Attributes\Trim;

final class ClassFormData
{
    #[Trim]
    #[Required]
    #[MaxLength(120)]
    public string $name;

    #[Trim]
    #[Nullable]
    #[MaxLength(32)]
    public string $joinCode = '';
}
