<?php

/**
 * @var list<object> $classes
 */
?>

<section class="page-head">
    <h1>My classes</h1>
</section>

<?php if ($classes === []): ?>
    <p class="empty">No classes assigned yet. Ask an admin to assign you.</p>
<?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Class</th>
                <th>Join code</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($classes as $schoolClass): ?>
                <tr>
                    <td><a href="/teach/classes/<?= (int) $schoolClass->id ?>"><?= $schoolClass->name ?></a></td>
                    <td class="muted"><code><?= $schoolClass->joinCode ?></code></td>
                    <td class="actions"><a href="/teach/classes/<?= (int) $schoolClass->id ?>">Manage</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
