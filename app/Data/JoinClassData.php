<?php

declare(strict_types=1);

namespace App\Data;

use Framework\Validation\Attributes\MaxLength;
use Framework\Validation\Attributes\Required;
use Framework\Validation\Attributes\Trim;

final class JoinClassData
{
    #[Trim]
    #[Required]
    #[MaxLength(32)]
    public string $joinCode;
}
