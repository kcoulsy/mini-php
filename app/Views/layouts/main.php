<?php

use Framework\Auth;
use Framework\View;

/**
 * @var \App\Models\User|null $user
 * @var string $unsafe_content
 * @var string $unsafe_scripts
 * @var string|null $title
 * @var string|null $flash
 */
$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Homework' ?> · Homework</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <a href="<?= Auth::check() ? Auth::homePath() : '/login' ?>" class="logo">Homework</a>
            <nav>
                <?php if (Auth::check() && $user !== null): ?>
                    <?php require dirname(__DIR__) . '/_nav.php'; ?>
                    <span class="nav-user"><?= View::e($user->name !== '' ? $user->name : $user->email) ?></span>
                    <form method="post" action="/logout" class="nav-logout">
                        <?= View::csrfField() ?>
                        <button type="submit" class="btn btn-ghost">Log out</button>
                    </form>
                <?php else: ?>
                    <a href="/login">Log in</a>
                    <a href="/register" class="btn btn-primary">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="container">
        <?php if (!empty($flash)): ?>
            <div class="flash" role="status"><?= View::e($flash) ?></div>
        <?php endif; ?>

        <?= $unsafe_content ?>
    </main>

    <footer class="site-footer">
        <div class="container">
            <small>Homework submission app · MiniPHP</small>
        </div>
    </footer>

    <?php if (!empty($unsafe_scripts)): ?>
        <?= $unsafe_scripts ?>
    <?php endif; ?>
</body>
</html>
