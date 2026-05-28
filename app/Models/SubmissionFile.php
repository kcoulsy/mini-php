<?php

declare(strict_types=1);

namespace App\Models;

use Framework\Database;
use Framework\FileStorage;
use Framework\UploadedFile;
use Framework\UploadValidator;

/**
 * @phpstan-type SubmissionFileRow array{
 *     id: int|string,
 *     submission_id: int|string,
 *     stored_path: string,
 *     original_name: string,
 *     mime_type: string,
 *     size_bytes: int|string,
 *     created_at: string
 * }
 */
final class SubmissionFile
{
    /** @return list<SubmissionFileRow> */
    public static function forSubmission(int $submissionId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM submission_files WHERE submission_id = :submission_id ORDER BY id ASC'
        );
        $stmt->execute(['submission_id' => $submissionId]);

        return $stmt->fetchAll();
    }

    /** @return SubmissionFileRow|null */
    public static function findForViewer(int $fileId, int $submissionId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM submission_files WHERE id = :id AND submission_id = :submission_id LIMIT 1'
        );
        $stmt->execute(['id' => $fileId, 'submission_id' => $submissionId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param list<UploadedFile> $files
     * @param array<string, mixed> $uploadConfig
     * @return list<string>
     */
    public static function createMany(
        int $submissionId,
        array $files,
        string $directory,
        array $uploadConfig,
        FileStorage $storage,
    ): array {
        if ($files === []) {
            return [];
        }

        $errors = UploadValidator::validateMany($uploadConfig, $files);

        if ($errors !== []) {
            return $errors;
        }

        foreach ($files as $file) {
            $storedPath = $storage->store($file, $directory);
            $stmt = Database::pdo()->prepare(
                'INSERT INTO submission_files (submission_id, stored_path, original_name, mime_type, size_bytes)
                 VALUES (:submission_id, :stored_path, :original_name, :mime_type, :size_bytes)'
            );
            $stmt->execute([
                'submission_id' => $submissionId,
                'stored_path' => $storedPath,
                'original_name' => $file->clientOriginalName(),
                'mime_type' => $file->detectedMimeType(),
                'size_bytes' => $file->size(),
            ]);
        }

        return [];
    }

    /** @param list<int> $ids */
    public static function deleteIds(int $submissionId, array $ids, FileStorage $storage): void
    {
        if ($ids === []) {
            return;
        }

        $attachments = self::forSubmission($submissionId);
        $allowedIds = [];

        foreach ($attachments as $attachment) {
            if (in_array((int) $attachment['id'], $ids, true)) {
                $allowedIds[] = (int) $attachment['id'];
            }
        }

        foreach ($allowedIds as $id) {
            $attachment = self::findByIdForSubmission($id, $submissionId);

            if ($attachment === null) {
                continue;
            }

            $storage->delete((string) $attachment['stored_path']);
            $stmt = Database::pdo()->prepare(
                'DELETE FROM submission_files WHERE id = :id AND submission_id = :submission_id'
            );
            $stmt->execute(['id' => $id, 'submission_id' => $submissionId]);
        }
    }

    public static function deleteAllForSubmission(int $submissionId, FileStorage $storage): void
    {
        foreach (self::forSubmission($submissionId) as $attachment) {
            $storage->delete((string) $attachment['stored_path']);
        }

        $stmt = Database::pdo()->prepare(
            'DELETE FROM submission_files WHERE submission_id = :submission_id'
        );
        $stmt->execute(['submission_id' => $submissionId]);
    }

    /** @return SubmissionFileRow|null */
    private static function findByIdForSubmission(int $id, int $submissionId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM submission_files WHERE id = :id AND submission_id = :submission_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'submission_id' => $submissionId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }
}
