<?php

use Framework\View;

/**
 * @var string $title
 * @var array<string, list<string>> $errors
 * @var array{email: string, name: string} $old
 * @var string $formAction
 */
?>

<section class="page-head">
    <h1>Create account</h1>
</section>

<form method="post" action="<?= $formAction ?>" class="card form-card">
    <?= View::csrfField() ?>
    <?php require dirname(__DIR__) . '/_form_errors.php'; ?>

    <label class="<?= View::hasFieldErrors($errors, 'name') ? 'label-invalid' : '' ?>">
        Name
        <input type="text" name="name" value="<?= $old['name'] ?>" maxlength="120" autocomplete="name">
        <?php View::fieldErrors($errors, 'name'); ?>
    </label>

    <label class="<?= View::hasFieldErrors($errors, 'email') ? 'label-invalid' : '' ?>">
        Email
        <input type="email" name="email" value="<?= $old['email'] ?>" required autocomplete="email">
        <?php View::fieldErrors($errors, 'email'); ?>
    </label>

    <?php
    $name = 'password';
    $label = 'Password';
    $autocomplete = 'new-password';
    $required = true;
    require __DIR__ . '/_password_field.php';

    $name = 'password_confirmation';
    $label = 'Confirm password';
    $autocomplete = 'new-password';
    $required = true;
    require __DIR__ . '/_password_field.php';
    ?>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Register</button>
        <a href="/login" class="btn btn-ghost">Log in</a>
    </div>
</form>

<?php require __DIR__ . '/_password_toggle_script.php'; ?>
