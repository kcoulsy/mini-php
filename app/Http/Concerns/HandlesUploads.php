<?php

declare(strict_types=1);

namespace App\Http\Concerns;

use App\Models\SubmissionFile;
use Framework\Auth;
use Framework\Database;
use Framework\FileStorage;
use Framework\Model\Collection;
use Framework\Request;
use Framework\UploadedFile;
use Framework\UploadValidator;

trait HandlesUploads
{
    /** @var array<string, mixed> */
    protected array $uploadConfig = [];

    protected FileStorage $storage;

    /** @param array<string, mixed> $uploadConfig */
    protected function bootUploads(array $uploadConfig): void
    {
        $this->uploadConfig = $uploadConfig;
        $this->storage = FileStorage::fromConfig($uploadConfig);
    }

    protected function userId(): int
    {
        $id = Auth::id();

        if ($id === null) {
            throw new \RuntimeException('Authenticated user required.');
        }

        return $id;
    }

    /** @return list<string> */
    protected function validateUploads(Request $request): array
    {
        $files = $request->files('attachments');

        if ($files === []) {
            return [];
        }

        return UploadValidator::validateMany($this->uploadConfig, $files);
    }

    /** @return list<int> */
    protected function removedFileIds(Request $request): array
    {
        $raw = $request->input('removed_attachment_ids', []);

        if (!is_array($raw)) {
            return [];
        }

        $ids = [];

        foreach ($raw as $value) {
            if (is_numeric($value)) {
                $ids[] = (int) $value;
            }
        }

        return $ids;
    }

    /** @return Collection<int, SubmissionFile> */
    protected function submissionFiles(int $submissionId): Collection
    {
        return SubmissionFile::query()
            ->where('submission_id', $submissionId)
            ->orderBy('id', 'asc')
            ->get();
    }

    protected function findSubmissionFile(int $fileId, int $submissionId): ?SubmissionFile
    {
        $file = SubmissionFile::query()
            ->where('id', $fileId)
            ->where('submission_id', $submissionId)
            ->first();

        return $file instanceof SubmissionFile ? $file : null;
    }

    /**
     * @param list<UploadedFile> $files
     * @return list<string>
     */
    protected function storeSubmissionFiles(int $submissionId, array $files, string $directory): array
    {
        if ($files === []) {
            return [];
        }

        $errors = UploadValidator::validateMany($this->uploadConfig, $files);

        if ($errors !== []) {
            return $errors;
        }

        foreach ($files as $file) {
            $storedPath = $this->storage->store($file, $directory);
            SubmissionFile::create([
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
    protected function removeSubmissionFiles(int $submissionId, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        foreach ($this->submissionFiles($submissionId) as $attachment) {
            if (!$attachment instanceof SubmissionFile) {
                continue;
            }

            if (!in_array($attachment->id, $ids, true)) {
                continue;
            }

            $this->storage->delete($attachment->storedPath);
            $attachment->delete();
        }
    }

    protected function deleteAllSubmissionFiles(int $submissionId): void
    {
        foreach ($this->submissionFiles($submissionId) as $attachment) {
            if ($attachment instanceof SubmissionFile) {
                $this->storage->delete($attachment->storedPath);
            }
        }

        $stmt = Database::pdo()->prepare(
            'DELETE FROM submission_files WHERE submission_id = :submission_id'
        );
        $stmt->execute(['submission_id' => $submissionId]);
    }
}
