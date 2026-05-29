<?php

declare(strict_types=1);

namespace App\Models;

use Framework\Model\Attributes\AutoIncrement;
use Framework\Model\Attributes\BelongsTo;
use Framework\Model\Attributes\Column;
use Framework\Model\Attributes\PrimaryKey;
use Framework\Model\Attributes\Table;
use Framework\Model\Model;

#[Table('submission_files')]
final class SubmissionFile extends Model
{
    #[PrimaryKey]
    #[AutoIncrement]
    #[Column]
    public int $id;

    #[Column(name: 'submission_id')]
    public int $submissionId;

    #[Column(name: 'stored_path')]
    public string $storedPath;

    #[Column(name: 'original_name')]
    public string $originalName;

    #[Column(name: 'mime_type')]
    public string $mimeType;

    #[Column(name: 'size_bytes')]
    public int $sizeBytes;

    #[Column]
    public string $createdAt;

    #[BelongsTo(Submission::class, foreignKey: 'submission_id', ownerKey: 'id')]
    public Submission $submission;
}
