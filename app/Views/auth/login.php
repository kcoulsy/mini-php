<?php

/**
 * @var string $title
 * @var list<string> $errors
 * @var array{email: string} $old
 * @var string $formAction
 */
?>

<section class="page-head">
    <h1>Log in</h1>
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
        Email
        <input type="email" name="email" value="<?= $old['email'] ?>" required autocomplete="email">
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
