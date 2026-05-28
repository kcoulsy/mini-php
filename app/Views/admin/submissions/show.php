<?php

use Framework\View;

/**
 * @var array<string, mixed> $submission
 * @var array<string, mixed>|null $assignment
 * @var array{id: int|string, email: string, name: string, role: string}|null $student
 * @var list<array{id: int|string, submission_id: int|string, stored_path: string, original_name: string, mime_type: string, size_bytes: int|string, created_at: string}> $files
 * @var array<string, list<string>> $errors
 */
$submissionId = (int) $submission['id'];
?>

<section class="page-head">
    <h1>Submission (admin)</h1>
</section>

<?php if ($student !== null): ?>
    <p>Student: <?= $student['name'] !== '' ? $student['name'] : $student['email'] ?></p>
<?php endif; ?>

<ul>
    <?php foreach ($files as $file): ?>
        <li>
            <a href="/admin/submissions/<?= $submissionId ?>/files/<?= (int) $file['id'] ?>">
                <?= $file['original_name'] ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<form method="post" action="/admin/submissions/<?= $submissionId ?>/grade" class="card form-card">
    <?= View::csrfField() ?>
    <?php require dirname(__DIR__, 2) . '/_form_errors.php'; ?>

    <label>
        Score (0–100)
        <input type="text" name="grade_score" value="<?= $submission['grade_score'] ?? '' ?>">
        <?php View::fieldErrors($errors, 'grade_score'); ?>
    </label>

    <label>
        Feedback
        <textarea name="grade_feedback" rows="4"><?= $submission['grade_feedback'] ?? '' ?></textarea>
    </label>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Override grade</button>
    </div>
</form>

<form method="post" action="/admin/submissions/<?= $submissionId ?>/delete" class="inline-form danger-zone">
    <?= View::csrfField() ?>
    <button type="submit" class="link-danger">Delete submission</button>
</form>
