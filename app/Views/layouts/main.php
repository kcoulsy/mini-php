<?php

/**
 * @var string $content
 * @var string|null $title
 * @var string|null $flash
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= \Framework\View::e($title ?? 'Items') ?> · MiniPHP</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <a href="/items" class="logo">MiniPHP</a>
            <nav>
                <a href="/items">All items</a>
                <a href="/items/create" class="btn btn-primary">New item</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <?php if (!empty($flash)): ?>
            <div class="flash" role="status"><?= \Framework\View::e($flash) ?></div>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <footer class="site-footer">
        <div class="container">
            <small>Pure PHP · front controller · MVC · no packages</small>
        </div>
    </footer>
</body>
</html>
