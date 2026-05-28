<?php

use Framework\View;

/**
 * @var array<string, list<string>> $errors
 * @var array{email: string, name: string, role: string} $old
 * @var list<string> $roles
 * @var string $formAction
 * @var string $cancelHref
 * @var array<string, mixed>|null $user
 */
$isEdit = $user !== null;
?>

<form method="post" action="<?= $formAction ?>" class="card form-card">
    <?= View::csrfField() ?>
    <?php require dirname(__DIR__, 2) . '/_form_errors.php'; ?>

    <label class="<?= View::hasFieldErrors($errors, 'email') ? 'label-invalid' : '' ?>">
        Email
        <input type="email" name="email" value="<?= $old['email'] ?>" required>
        <?php View::fieldErrors($errors, 'email'); ?>
    </label>

    <label class="<?= View::hasFieldErrors($errors, 'name') ? 'label-invalid' : '' ?>">
        Name
        <input type="text" name="name" value="<?= $old['name'] ?>">
        <?php View::fieldErrors($errors, 'name'); ?>
    </label>

    <label class="<?= View::hasFieldErrors($errors, 'role') ? 'label-invalid' : '' ?>">
        Role
        <select name="role" required>
            <?php foreach ($roles as $role): ?>
                <option value="<?= $role ?>" <?= $old['role'] === $role ? 'selected' : '' ?>><?= $role ?></option>
            <?php endforeach; ?>
        </select>
        <?php View::fieldErrors($errors, 'role'); ?>
    </label>

    <label class="<?= View::hasFieldErrors($errors, 'password') ? 'label-invalid' : '' ?>">
        Password<?= $isEdit ? ' (leave blank to keep)' : '' ?>
        <input type="password" name="password" <?= $isEdit ? '' : 'required' ?> autocomplete="new-password">
        <?php View::fieldErrors($errors, 'password'); ?>
    </label>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="<?= $cancelHref ?>" class="btn btn-ghost">Cancel</a>
    </div>
</form>
