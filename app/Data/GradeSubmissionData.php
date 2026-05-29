<?php

declare(strict_types=1);

namespace App\Data;

use App\Validation\GradeScore;
use Framework\Validation\Attributes\MaxLength;
use Framework\Validation\Attributes\Nullable;
use Framework\Validation\Attributes\Trim;

final class GradeSubmissionData
{
    #[Trim]
    #[Nullable]
    #[GradeScore]
    public ?string $gradeScore = null;

    #[Trim]
    #[Nullable]
    #[MaxLength(5000)]
    public string $gradeFeedback = '';
}
