<?php

use Framework\View;

/**
 * @var array<string, list<string>> $errors
 * @var array{class_id: string, title: string, description: string, due_at: string} $old
 * @var list<array{id: int|string, name: string, join_code: string, created_at: string, updated_at: string}> $classes
 * @var string $formAction
 * @var string $cancelHref
 */
?>

<form method="post" action="<?= $formAction ?>" class="card form-card">
    <?= View::csrfField() ?>
    <?php require dirname(__DIR__, 2) . '/_form_errors.php'; ?>

    <label class="<?= View::hasFieldErrors($errors, 'class_id') ? 'label-invalid' : '' ?>">
        Class
        <select name="class_id" required>
            <option value="">Select…</option>
            <?php foreach ($classes as $schoolClass): ?>
                <option value="<?= (int) $schoolClass['id'] ?>" <?= $old['class_id'] === (string) $schoolClass['id'] ? 'selected' : '' ?>>
                    <?= $schoolClass['name'] ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php View::fieldErrors($errors, 'class_id'); ?>
    </label>

    <label class="<?= View::hasFieldErrors($errors, 'title') ? 'label-invalid' : '' ?>">
        Title
        <input type="text" name="title" value="<?= $old['title'] ?>" maxlength="120" required>
        <?php View::fieldErrors($errors, 'title'); ?>
    </label>

    <label>
        Description
        <textarea name="description" rows="6" maxlength="5000"><?= $old['description'] ?></textarea>
    </label>

    <label>
        Due at (optional)
        <input type="text" name="due_at" value="<?= $old['due_at'] ?>" placeholder="YYYY-MM-DD HH:MM:SS">
    </label>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="<?= $cancelHref ?>" class="btn btn-ghost">Cancel</a>
    </div>
</form>
