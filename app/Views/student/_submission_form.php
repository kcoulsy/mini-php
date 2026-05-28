<?php

use Framework\View;

/**
 * @var array<string, list<string>> $errors
 * @var int $assignmentId
 * @var int|null $submissionId
 * @var list<array{
 *     id: int|string,
 *     submission_id: int|string,
 *     stored_path: string,
 *     original_name: string,
 *     mime_type: string,
 *     size_bytes: int|string,
 *     created_at: string
 * }> $files
 */
$submissionId = $submissionId ?? null;
$files = $files ?? [];
$existingFiles = [];

foreach ($files as $file) {
    $fid = (int) $file['id'];
    $sid = (int) $submissionId;
    $existingFiles[] = [
        'source' => '/student/submissions/' . $sid . '/files/' . $fid,
        'options' => [
            'type' => 'local',
            'metadata' => ['attachmentId' => $fid],
            'file' => [
                'name' => (string) $file['original_name'],
                'size' => (int) $file['size_bytes'],
                'type' => (string) $file['mime_type'],
            ],
        ],
    ];
}

$existingJson = json_encode($existingFiles, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>

<link rel="stylesheet" href="/assets/filepond/filepond.min.css">

<form method="post" action="/student/assignments/<?= $assignmentId ?>" class="card form-card" enctype="multipart/form-data">
    <?= View::csrfField() ?>
    <?php require dirname(__DIR__) . '/_form_errors.php'; ?>

    <label class="<?= View::hasFieldErrors($errors, 'attachments') ? 'label-invalid' : '' ?>">
        Files
        <input
            type="file"
            id="submission-attachments"
            name="attachments[]"
            multiple
            data-existing-files="<?= View::e($existingJson) ?>"
        >
        <?php View::fieldErrors($errors, 'attachments'); ?>
    </label>

    <div id="removed-attachment-ids"></div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save submission</button>
        <a href="/student/assignments/<?= $assignmentId ?>" class="btn btn-ghost">Cancel</a>
    </div>
</form>

<?php
View::script('<script src="/assets/filepond/filepond.min.js"></script>', 'filepond-lib');
View::script('<script src="/assets/submission-filepond.js"></script>', 'submission-filepond');
?>
