<?php

/**
 * @var list<string> $errors
 * @var array{title: string, description: string} $old
 * @var string $formAction
 * @var string $cancelHref
 */
?>

<?php if ($errors !== []): ?>
    <ul class="errors">
        <?php foreach ($errors as $error): ?>
            <li><?= \Framework\View::e($error) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post" action="<?= \Framework\View::e($formAction) ?>" class="card form-card">
    <label>
        Title
        <input type="text" name="title" value="<?= \Framework\View::e($old['title']) ?>" maxlength="120" required>
    </label>

    <label>
        Description
        <textarea name="description" rows="6" maxlength="2000"><?= \Framework\View::e($old['description']) ?></textarea>
    </label>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="<?= \Framework\View::e($cancelHref) ?>" class="btn btn-ghost">Cancel</a>
    </div>
</form>
