<?php

/**
 * @var array{id: int|string, title: string, description: string, created_at: string, updated_at: string} $item
 */

$title = $item['title'];
?>

<article class="card">
    <header class="card-header">
        <h1><?= $item['title'] ?></h1>
        <div class="card-actions">
            <a href="/items/<?= (int) $item['id'] ?>/edit" class="btn">Edit</a>
            <a href="/items" class="btn btn-ghost">Back</a>
        </div>
    </header>

    <p class="item-description"><?= nl2br((string) $item['description']) ?></p>

    <footer class="meta muted">
        Created <?= $item['created_at'] ?> · Updated <?= $item['updated_at'] ?>
    </footer>
</article>
