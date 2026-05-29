<?php

use Framework\View;

/**
 * @var object $submission
 * @var object|null $assignment
 * @var object|null $student
 * @var list<object> $files
 * @var array<string, list<string>> $errors
 */
$submissionId = (int) $submission->id;
?>

<section class="page-head">
    <h1>Submission (admin)</h1>
</section>

<?php if ($student !== null): ?>
    <p>Student: <?= $student->name !== '' ? $student->name : $student->email ?></p>
<?php endif; ?>

<ul>
    <?php foreach ($files as $file): ?>
        <li>
            <a href="/admin/submissions/<?= $submissionId ?>/files/<?= (int) $file->id ?>">
                <?= $file->originalName ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<form method="post" action="/admin/submissions/<?= $submissionId ?>/grade" class="card form-card">
    <?= View::csrfField() ?>
    <?php require dirname(__DIR__, 2) . '/_form_errors.php'; ?>

    <label>
        Score (0–100)
        <input type="text" name="grade_score" value="<?= $submission->gradeScore ?? '' ?>">
        <?php View::fieldErrors($errors, 'grade_score'); ?>
    </label>

    <label>
        Feedback
        <textarea name="grade_feedback" rows="4"><?= $submission->gradeFeedback ?? '' ?></textarea>
    </label>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Override grade</button>
    </div>
</form>

<form method="post" action="/admin/submissions/<?= $submissionId ?>/delete" class="inline-form danger-zone">
    <?= View::csrfField() ?>
    <button type="submit" class="link-danger">Delete submission</button>
</form>
