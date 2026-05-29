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
 * @var object|null $submission
 * @var list<object> $files
 * @var bool $canEdit
 * @var array<string, list<string>> $errors
 */
$aid = (int) $assignment->id;
$submissionId = $submission !== null ? (int) $submission->id : null;
?>

<section class="page-head">
    <h1><?= $assignment->title ?></h1>
    <a href="/student/classes/<?= (int) $assignment->classId ?>" class="btn btn-ghost">Back to class</a>
</section>

<div class="card prose">
    <?php if ($assignment->description !== ''): ?>
        <p><?= nl2br($assignment->description) ?></p>
    <?php endif; ?>
    <p class="muted">Due: <?= $assignment->dueAt ?? 'No due date' ?></p>

    <?php if ($submission !== null && !empty($submission->gradedAt)): ?>
        <p><strong>Grade:</strong>
            <?= $submission->gradeScore !== null ? $submission->gradeScore : '—' ?>
        </p>
        <?php if (($submission->gradeFeedback ?? '') !== ''): ?>
            <p><strong>Feedback:</strong> <?= $submission->gradeFeedback ?></p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php if ($files !== []): ?>
    <h2>Uploaded files</h2>
    <ul>
        <?php foreach ($files as $file): ?>
            <li>
                <a href="/student/submissions/<?= (int) $submissionId ?>/files/<?= (int) $file->id ?>">
                    <?= $file->originalName ?>
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
