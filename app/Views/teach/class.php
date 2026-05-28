<?php

/**
 * @var array{id: int|string, name: string, join_code: string, created_at: string, updated_at: string} $schoolClass
 * @var list<array{id: int|string, class_id: int|string, title: string, description: string, due_at: string|null, created_by: int|string, created_at: string, updated_at: string}> $assignments
 * @var list<array{id: int|string, email: string, name: string, role: string}> $students
 */
$cid = (int) $schoolClass['id'];
?>

<section class="page-head">
    <h1><?= $schoolClass['name'] ?></h1>
    <a href="/teach/classes/<?= $cid ?>/assignments/create" class="btn btn-primary">New assignment</a>
    <a href="/teach" class="btn btn-ghost">Back</a>
</section>

<p class="muted">Share join code <code><?= $schoolClass['join_code'] ?></code> with students.</p>

<h2>Assignments</h2>
<?php if ($assignments === []): ?>
    <p class="empty">No assignments yet.</p>
<?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Due</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($assignments as $assignment): ?>
                <tr>
                    <td><?= $assignment['title'] ?></td>
                    <td class="muted"><?= $assignment['due_at'] ?? '—' ?></td>
                    <td class="actions">
                        <a href="/teach/classes/<?= $cid ?>/assignments/<?= (int) $assignment['id'] ?>/submissions">Submissions</a>
                        <a href="/teach/classes/<?= $cid ?>/assignments/<?= (int) $assignment['id'] ?>/edit">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2>Students</h2>
<?php if ($students === []): ?>
    <p class="empty">No students enrolled.</p>
<?php else: ?>
    <ul>
        <?php foreach ($students as $student): ?>
            <li><?= $student['name'] !== '' ? $student['name'] : $student['email'] ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
