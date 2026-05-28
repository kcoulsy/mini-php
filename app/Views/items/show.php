<?php

/**
 * @var array{id: int|string, title: string, description: string, created_at: string, updated_at: string} $item
 * @var list<array<string, mixed>> $attachments
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

    <?php if ($attachments !== []): ?>
        <section class="item-attachments">
            <h2 class="h3">Attachments</h2>
            <ul>
                <?php foreach ($attachments as $attachment): ?>
                    <li>
                        <a href="/items/<?= (int) $item['id'] ?>/attachments/<?= (int) $attachment['id'] ?>">
                            <?= $attachment['original_name'] ?>
                        </a>
                        <span class="muted">(<?= number_format(((int) $attachment['size_bytes']) / 1024, 1) ?> KB)</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <footer class="meta muted">
        Created <?= $item['created_at'] ?> · Updated <?= $item['updated_at'] ?>
    </footer>
</article>
