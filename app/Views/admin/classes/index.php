<?php

use Framework\View;

/**
 * @var list<array{id: int|string, name: string, join_code: string, created_at: string, updated_at: string}> $classes
 */
?>

<section class="page-head">
    <h1>Classes</h1>
    <a href="/admin/classes/create" class="btn btn-primary">New class</a>
</section>

<table class="data-table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Join code</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($classes as $schoolClass): ?>
            <tr>
                <td><?= $schoolClass['name'] ?></td>
                <td><code><?= $schoolClass['join_code'] ?></code></td>
                <td class="actions">
                    <a href="/admin/classes/<?= (int) $schoolClass['id'] ?>/edit">Edit</a>
                    <form method="post" action="/admin/classes/<?= (int) $schoolClass['id'] ?>/delete" class="inline-form">
                        <?= View::csrfField() ?>
                        <button type="submit" class="link-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
