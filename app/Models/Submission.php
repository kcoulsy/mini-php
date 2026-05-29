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

#[Table('submissions')]
final class Submission extends Model
{
    #[PrimaryKey]
    #[AutoIncrement]
    #[Column(type: 'integer')]
    public int $id;

    #[Column(name: 'assignment_id', type: 'integer')]
    public int $assignmentId;

    #[Column(name: 'student_id', type: 'integer')]
    public int $studentId;

    #[Column(name: 'submitted_at', type: 'text', nullable: true)]
    public ?string $submittedAt;

    #[Column(name: 'grade_score', type: 'real', nullable: true)]
    public ?float $gradeScore;

    #[Column(name: 'grade_feedback', type: 'text')]
    public string $gradeFeedback;

    #[Column(name: 'graded_by', type: 'integer', nullable: true)]
    public ?int $gradedBy;

    #[Column(name: 'graded_at', type: 'text', nullable: true)]
    public ?string $gradedAt;

    #[Column(name: 'created_at', type: 'text')]
    public string $createdAt;

    #[Column(name: 'updated_at', type: 'text')]
    public string $updatedAt;

    #[BelongsTo(Assignment::class, foreignKey: 'assignment_id', ownerKey: 'id')]
    public Assignment $assignment;

    #[BelongsTo(User::class, foreignKey: 'student_id', ownerKey: 'id')]
    public User $student;

    #[HasMany(SubmissionFile::class, foreignKey: 'submission_id', localKey: 'id')]
    public Collection $files;

    public static function upsertForStudent(int $assignmentId, int $studentId): self
    {
        $existing = self::query()
            ->where('assignment_id', $assignmentId)
            ->where('student_id', $studentId)
            ->first();

        if ($existing instanceof self) {
            $existing->submittedAt = date('Y-m-d H:i:s');
            $existing->save();

            return $existing;
        }

        return self::create([
            'assignment_id' => $assignmentId,
            'student_id' => $studentId,
            'submitted_at' => date('Y-m-d H:i:s'),
            'grade_feedback' => '',
        ]);
    }

    public function grade(?float $score, string $feedback, int $gradedBy): bool
    {
        $this->gradeScore = $score;
        $this->gradeFeedback = trim($feedback);
        $this->gradedBy = $gradedBy;
        $this->gradedAt = date('Y-m-d H:i:s');

        return $this->save();
    }

    public function isGraded(): bool
    {
        return $this->gradedAt !== null && $this->gradedAt !== '';
    }
}
