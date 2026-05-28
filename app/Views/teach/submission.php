<?php

use Framework\View;

/**
 * @var array<string, mixed> $submission
 * @var array<string, mixed>|null $assignment
 * @var array{id: int|string, email: string, name: string, role: string}|null $student
 * @var list<array{id: int|string, submission_id: int|string, stored_path: string, original_name: string, mime_type: string, size_bytes: int|string, created_at: string}> $files
 * @var bool $canGrade
 * @var array<string, list<string>> $errors
 */
$submissionId = (int) $submission['id'];
?>

<section class="page-head">
    <h1>Submission</h1>
    <?php if ($assignment !== null && $student !== null): ?>
        <p class="muted">
            <?= $assignment['title'] ?> —
            <?= $student['name'] !== '' ? $student['name'] : $student['email'] ?>
        </p>
    <?php endif; ?>
</section>

<h2>Files</h2>
<?php if ($files === []): ?>
    <p class="empty">No files.</p>
<?php else: ?>
    <ul>
        <?php foreach ($files as $file): ?>
            <li>
                <a href="/teach/submissions/<?= $submissionId ?>/files/<?= (int) $file['id'] ?>">
                    <?= $file['original_name'] ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if (!empty($submission['graded_at'])): ?>
    <p><strong>Current grade:</strong> <?= $submission['grade_score'] ?? '—' ?></p>
    <?php if (($submission['grade_feedback'] ?? '') !== ''): ?>
        <p><strong>Feedback:</strong> <?= $submission['grade_feedback'] ?></p>
    <?php endif; ?>
<?php endif; ?>

<?php if ($canGrade): ?>
    <h2>Grade</h2>
    <form method="post" action="/teach/submissions/<?= $submissionId ?>/grade" class="card form-card">
        <?= View::csrfField() ?>
        <?php require dirname(__DIR__) . '/_form_errors.php'; ?>

        <label class="<?= View::hasFieldErrors($errors, 'grade_score') ? 'label-invalid' : '' ?>">
            Score (0–100, optional)
            <input type="text" name="grade_score" value="<?= $submission['grade_score'] ?? '' ?>">
            <?php View::fieldErrors($errors, 'grade_score'); ?>
        </label>

        <label class="<?= View::hasFieldErrors($errors, 'grade_feedback') ? 'label-invalid' : '' ?>">
            Feedback
            <textarea name="grade_feedback" rows="4" maxlength="5000"><?= $submission['grade_feedback'] ?? '' ?></textarea>
            <?php View::fieldErrors($errors, 'grade_feedback'); ?>
        </label>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save grade</button>
        </div>
    </form>
<?php endif; ?>
