<?php

/**
 * @var string $title
 * @var list<string> $errors
 * @var array{email: string, name: string} $old
 * @var string $formAction
 */
?>

<section class="page-head">
    <h1>Create account</h1>
</section>

<?php if ($errors !== []): ?>
    <ul class="errors">
        <?php foreach ($errors as $error): ?>
            <li><?= $error ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post" action="<?= $formAction ?>" class="card form-card">
    <?= \Framework\View::csrfField() ?>
    <label>
        Name
        <input type="text" name="name" value="<?= $old['name'] ?>" maxlength="120" autocomplete="name">
    </label>

    <label>
        Email
        <input type="email" name="email" value="<?= $old['email'] ?>" required autocomplete="email">
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
