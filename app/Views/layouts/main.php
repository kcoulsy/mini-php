<?php

use Framework\Auth;
use Framework\View;

/**
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
    <title><?= $title ?? 'Items' ?> · MiniPHP</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <a href="<?= Auth::check() ? '/items' : '/login' ?>" class="logo">MiniPHP</a>
            <nav>
                <?php if (Auth::check() && is_array($user)): ?>
                    <a href="/items">All items</a>
                    <a href="/items/create" class="btn btn-primary">New item</a>
                    <span class="nav-user"><?= View::e((string) ($user['name'] !== '' ? $user['name'] : $user['email'])) ?></span>
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
            <small>Pure PHP · front controller · MVC · no packages</small>
        </div>
    </footer>

    <?php if (!empty($unsafe_scripts)): ?>
        <?= $unsafe_scripts ?>
    <?php endif; ?>
</body>
</html>
