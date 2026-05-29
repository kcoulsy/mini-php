<?php

declare(strict_types=1);

namespace Tests\Framework\Model\Fixtures;

use Framework\Model\Attributes\AutoIncrement;
use Framework\Model\Attributes\BelongsTo;
use Framework\Model\Attributes\Column;
use Framework\Model\Attributes\HasMany;
use Framework\Model\Attributes\PrimaryKey;
use Framework\Model\Attributes\Table;
use Framework\Model\Collection;
use Framework\Model\Model;

#[Table('assignments')]
final class TestAssignment extends Model
{
    #[PrimaryKey]
    #[AutoIncrement]
    #[Column]
    public int $id;

    #[Column]
    public int $classId;

    #[Column]
    public string $title;

    #[Column]
    public string $description;

    #[Column]
    public ?string $dueAt;

    #[Column]
    public int $createdBy;

    #[Column]
    public string $createdAt;

    #[Column]
    public string $updatedAt;

    #[BelongsTo(TestUser::class, foreignKey: 'created_by', ownerKey: 'id')]
    public TestUser $creator;

    #[HasMany(TestSubmission::class, foreignKey: 'assignment_id', localKey: 'id')]
    public Collection $submissions;
}
