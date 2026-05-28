<?php

use Framework\View;

/**
 * @var string $title
 * @var array<string, list<string>> $errors
 * @var array{email: string} $old
 * @var string $formAction
 */
?>

<section class="page-head">
    <h1>Log in</h1>
</section>

<form method="post" action="<?= $formAction ?>" class="card form-card">
    <?= View::csrfField() ?>
    <?php require dirname(__DIR__) . '/_form_errors.php'; ?>

    <label class="<?= View::hasFieldErrors($errors, 'email') ? 'label-invalid' : '' ?>">
        Email
        <input type="email" name="email" value="<?= $old['email'] ?>" required autocomplete="email">
        <?php View::fieldErrors($errors, 'email'); ?>
    </label>

    <?php
    $name = 'password';
    $label = 'Password';
    $autocomplete = 'current-password';
    $required = true;
    require __DIR__ . '/_password_field.php';
    ?>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Log in</button>
        <a href="/register" class="btn btn-ghost">Create account</a>
    </div>
</form>

<?php require __DIR__ . '/_password_toggle_script.php'; ?>
