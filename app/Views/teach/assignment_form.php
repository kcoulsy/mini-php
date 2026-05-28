<?php

use Framework\View;

/**
 * @var array{id: int|string, name: string, join_code: string, created_at: string, updated_at: string} $schoolClass
 * @var array<string, list<string>> $errors
 * @var array{title: string, description: string, due_at: string} $old
 * @var string $formAction
 * @var string $cancelHref
 */
?>

<form method="post" action="<?= $formAction ?>" class="card form-card">
    <?= View::csrfField() ?>
    <?php require dirname(__DIR__) . '/_form_errors.php'; ?>

    <label class="<?= View::hasFieldErrors($errors, 'title') ? 'label-invalid' : '' ?>">
        Title
        <input type="text" name="title" value="<?= $old['title'] ?>" maxlength="120" required>
        <?php View::fieldErrors($errors, 'title'); ?>
    </label>

    <label class="<?= View::hasFieldErrors($errors, 'description') ? 'label-invalid' : '' ?>">
        Description
        <textarea name="description" rows="6" maxlength="5000"><?= $old['description'] ?></textarea>
        <?php View::fieldErrors($errors, 'description'); ?>
    </label>

    <label class="<?= View::hasFieldErrors($errors, 'due_at') ? 'label-invalid' : '' ?>">
        Due at (optional, e.g. 2026-06-01 23:59:00)
        <input type="text" name="due_at" value="<?= $old['due_at'] ?>" placeholder="YYYY-MM-DD HH:MM:SS">
        <?php View::fieldErrors($errors, 'due_at'); ?>
    </label>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="<?= $cancelHref ?>" class="btn btn-ghost">Cancel</a>
    </div>
</form>
