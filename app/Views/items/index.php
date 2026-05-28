<?php

/**
 * @var list<array{id: int|string, title: string, description: string, created_at: string, updated_at: string}> $items
 * @var string|null $flash
 */

$title = 'Items';
?>

<section class="page-head">
    <h1>Items</h1>
    <a href="/items/create" class="btn btn-primary">Create item</a>
</section>

<?php if ($items === []): ?>
    <p class="empty">No items yet. <a href="/items/create">Create the first one</a>.</p>
<?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Updated</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <a href="/items/<?= (int) $item['id'] ?>"><?= \Framework\View::e($item['title']) ?></a>
                    </td>
                    <td class="muted"><?= \Framework\View::e($item['updated_at']) ?></td>
                    <td class="actions">
                        <a href="/items/<?= (int) $item['id'] ?>/edit">Edit</a>
                        <form method="post" action="/items/<?= (int) $item['id'] ?>/delete" class="inline-form"
                              onsubmit="return confirm('Delete this item?');">
                            <button type="submit" class="link-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
