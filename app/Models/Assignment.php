<?php

declare(strict_types=1);

namespace App\Models;

use Framework\Model\Attributes\AutoIncrement;
use Framework\Model\Attributes\BelongsTo;
use Framework\Model\Attributes\Column;
use Framework\Model\Attributes\HasMany;
use Framework\Model\Attributes\PrimaryKey;
use Framework\Model\Attributes\Table;
use Framework\Model\Collection;
use Framework\Model\Model;

#[Table('assignments')]
final class Assignment extends Model
{
    #[PrimaryKey]
    #[AutoIncrement]
    #[Column(type: 'integer')]
    public int $id;

    #[Column(name: 'class_id', type: 'integer')]
    public int $classId;

    #[Column(type: 'text')]
    public string $title;

    #[Column(type: 'text')]
    public string $description;

    #[Column(name: 'due_at', type: 'text', nullable: true)]
    public ?string $dueAt;

    #[Column(name: 'created_by', type: 'integer')]
    public int $createdBy;

    #[Column(name: 'created_at', type: 'text')]
    public string $createdAt;

    #[Column(name: 'updated_at', type: 'text')]
    public string $updatedAt;

    #[BelongsTo(SchoolClass::class, foreignKey: 'class_id', ownerKey: 'id')]
    public SchoolClass $schoolClass;

    #[BelongsTo(User::class, foreignKey: 'created_by', ownerKey: 'id')]
    public User $creator;

    #[HasMany(Submission::class, foreignKey: 'assignment_id', localKey: 'id')]
    public Collection $submissions;

    public function isPastDue(): bool
    {
        if ($this->dueAt === null || $this->dueAt === '') {
            return false;
        }

        return strtotime($this->dueAt) < time();
    }
}
