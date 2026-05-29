<?php

declare(strict_types=1);

namespace App\Data;

use Framework\Validation\Attributes\MaxLength;
use Framework\Validation\Attributes\Nullable;
use Framework\Validation\Attributes\Required;
use Framework\Validation\Attributes\Trim;

final class AssignmentFormData
{
    #[Trim]
    #[Required]
    #[MaxLength(120)]
    public string $title;

    #[Trim]
    #[Nullable]
    #[MaxLength(5000)]
    public string $description = '';

    #[Trim]
    #[Nullable]
    public ?string $dueAt = null;
}
