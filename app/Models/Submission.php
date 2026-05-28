<?php

declare(strict_types=1);

namespace App\Models;

use Framework\Database;

/**
 * @phpstan-type SubmissionRow array{
 *     id: int|string,
 *     assignment_id: int|string,
 *     student_id: int|string,
 *     submitted_at: string|null,
 *     grade_score: float|int|string|null,
 *     grade_feedback: string,
 *     graded_by: int|string|null,
 *     graded_at: string|null,
 *     created_at: string,
 *     updated_at: string
 * }
 */
final class Submission
{
    /** @return SubmissionRow|null */
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM submissions WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** @return SubmissionRow|null */
    public static function findForStudent(int $assignmentId, int $studentId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM submissions WHERE assignment_id = :assignment_id AND student_id = :student_id LIMIT 1'
        );
        $stmt->execute(['assignment_id' => $assignmentId, 'student_id' => $studentId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** @return list<SubmissionRow> */
    public static function forAssignment(int $assignmentId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM submissions WHERE assignment_id = :assignment_id ORDER BY student_id ASC'
        );
        $stmt->execute(['assignment_id' => $assignmentId]);

        return $stmt->fetchAll();
    }

    public static function upsertForStudent(int $assignmentId, int $studentId): int
    {
        $existing = self::findForStudent($assignmentId, $studentId);

        if ($existing !== null) {
            $stmt = Database::pdo()->prepare(
                'UPDATE submissions SET submitted_at = datetime(\'now\'), updated_at = datetime(\'now\')
                 WHERE id = :id'
            );
            $stmt->execute(['id' => $existing['id']]);

            return (int) $existing['id'];
        }

        $stmt = Database::pdo()->prepare(
            'INSERT INTO submissions (assignment_id, student_id, submitted_at)
             VALUES (:assignment_id, :student_id, datetime(\'now\'))'
        );
        $stmt->execute([
            'assignment_id' => $assignmentId,
            'student_id' => $studentId,
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function grade(
        int $id,
        ?float $score,
        string $feedback,
        int $gradedBy,
    ): bool {
        $stmt = Database::pdo()->prepare(
            'UPDATE submissions SET grade_score = :grade_score, grade_feedback = :grade_feedback,
             graded_by = :graded_by, graded_at = datetime(\'now\'), updated_at = datetime(\'now\')
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'grade_score' => $score,
            'grade_feedback' => trim($feedback),
            'graded_by' => $gradedBy,
        ]);

        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::pdo()->prepare('DELETE FROM submissions WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public static function isGraded(array $submission): bool
    {
        return $submission['graded_at'] !== null && $submission['graded_at'] !== '';
    }
}
