<?php

/**
 * @var array{
 *     id: int|string,
 *     class_id: int|string,
 *     title: string,
 *     description: string,
 *     due_at: string|null,
 *     created_by: int|string,
 *     created_at: string,
 *     updated_at: string
 * } $assignment
 * @var array<string, mixed>|null $submission
 * @var list<array{
 *     id: int|string,
 *     submission_id: int|string,
 *     stored_path: string,
 *     original_name: string,
 *     mime_type: string,
 *     size_bytes: int|string,
 *     created_at: string
 * }> $files
 * @var bool $canEdit
 * @var array<string, list<string>> $errors
 */
$aid = (int) $assignment['id'];
$submissionId = $submission !== null ? (int) $submission['id'] : null;
?>

<section class="page-head">
    <h1><?= $assignment['title'] ?></h1>
    <a href="/student/classes/<?= (int) $assignment['class_id'] ?>" class="btn btn-ghost">Back to class</a>
</section>

<div class="card prose">
    <?php if ($assignment['description'] !== ''): ?>
        <p><?= nl2br($assignment['description']) ?></p>
    <?php endif; ?>
    <p class="muted">Due: <?= $assignment['due_at'] ?? 'No due date' ?></p>

    <?php if ($submission !== null && !empty($submission['graded_at'])): ?>
        <p><strong>Grade:</strong>
            <?= $submission['grade_score'] !== null ? $submission['grade_score'] : '—' ?>
        </p>
        <?php if (($submission['grade_feedback'] ?? '') !== ''): ?>
            <p><strong>Feedback:</strong> <?= $submission['grade_feedback'] ?></p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php if ($files !== []): ?>
    <h2>Uploaded files</h2>
    <ul>
        <?php foreach ($files as $file): ?>
            <li>
                <a href="/student/submissions/<?= (int) $submissionId ?>/files/<?= (int) $file['id'] ?>">
                    <?= $file['original_name'] ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if ($canEdit): ?>
    <h2><?= $submission !== null ? 'Update submission' : 'Submit work' ?></h2>
    <?php require __DIR__ . '/_submission_form.php'; ?>
<?php elseif ($submission === null): ?>
    <p class="empty">Submissions are closed for this assignment.</p>
<?php endif; ?>
