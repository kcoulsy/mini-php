<?php

declare(strict_types=1);

namespace Tests\Framework\Model\Fixtures;

use Framework\Model\Attributes\AutoIncrement;
use Framework\Model\Attributes\Column;
use Framework\Model\Attributes\PrimaryKey;
use Framework\Model\Attributes\Table;
use Framework\Model\Model;

#[Table('submissions')]
final class TestSubmission extends Model
{
    #[PrimaryKey]
    #[AutoIncrement]
    #[Column]
    public int $id;

    #[Column]
    public int $assignmentId;

    #[Column]
    public int $studentId;

    #[Column]
    public ?string $submittedAt;

    #[Column]
    public ?float $gradeScore;

    #[Column]
    public string $gradeFeedback;

    #[Column]
    public ?int $gradedBy;

    #[Column]
    public ?string $gradedAt;

    #[Column]
    public string $createdAt;

    #[Column]
    public string $updatedAt;
}
