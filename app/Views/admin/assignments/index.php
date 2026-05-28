<?php

use Framework\View;

/**
 * @var list<array{id: int|string, class_id: int|string, title: string, description: string, due_at: string|null, created_by: int|string, created_at: string, updated_at: string}> $assignments
 * @var list<array{id: int|string, name: string, join_code: string, created_at: string, updated_at: string}> $classes
 * @var int $classFilter
 */
$classNames = [];

foreach ($classes as $c) {
    $classNames[(int) $c['id']] = (string) $c['name'];
}
?>

<section class="page-head">
    <h1>Assignments</h1>
    <a href="/admin/assignments/create" class="btn btn-primary">New assignment</a>
</section>

<form method="get" action="/admin/assignments" class="filter-form">
    <label>
        Filter by class
        <select name="class_id" onchange="this.form.submit()">
            <option value="0">All classes</option>
            <?php foreach ($classes as $schoolClass): ?>
                <option value="<?= (int) $schoolClass['id'] ?>" <?= $classFilter === (int) $schoolClass['id'] ? 'selected' : '' ?>>
                    <?= $schoolClass['name'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
</form>

<table class="data-table">
    <thead>
        <tr>
            <th>Title</th>
            <th>Class</th>
            <th>Due</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($assignments as $assignment): ?>
            <tr>
                <td><?= $assignment['title'] ?></td>
                <td class="muted"><?= $classNames[(int) $assignment['class_id']] ?? '—' ?></td>
                <td class="muted"><?= $assignment['due_at'] ?? '—' ?></td>
                <td class="actions">
                    <a href="/admin/assignments/<?= (int) $assignment['id'] ?>/edit">Edit</a>
                    <form method="post" action="/admin/assignments/<?= (int) $assignment['id'] ?>/delete" class="inline-form">
                        <?= View::csrfField() ?>
                        <button type="submit" class="link-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
