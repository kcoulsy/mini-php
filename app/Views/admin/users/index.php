<?php

use Framework\View;

/**
 * @var list<array{id: int|string, email: string, name: string, role: string, created_at: string, updated_at: string}> $users
 */
?>

<section class="page-head">
    <h1>Users</h1>
    <a href="/admin/users/create" class="btn btn-primary">New user</a>
</section>

<table class="data-table">
    <thead>
        <tr>
            <th>Email</th>
            <th>Name</th>
            <th>Role</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= $user['email'] ?></td>
                <td><?= $user['name'] ?></td>
                <td><?= $user['role'] ?></td>
                <td class="actions">
                    <a href="/admin/users/<?= (int) $user['id'] ?>/edit">Edit</a>
                    <form method="post" action="/admin/users/<?= (int) $user['id'] ?>/delete" class="inline-form">
                        <?= View::csrfField() ?>
                        <button type="submit" class="link-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
