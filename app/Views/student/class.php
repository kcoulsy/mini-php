<?php

/**
 * @var object $schoolClass
 * @var list<object> $assignments
 * @var array<int, object|null> $submissions
 */
?>

<section class="page-head">
    <h1><?= $schoolClass->name ?></h1>
    <a href="/student" class="btn btn-ghost">Back</a>
</section>

<?php if ($assignments === []): ?>
    <p class="empty">No assignments yet.</p>
<?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Assignment</th>
                <th>Due</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($assignments as $assignment): ?>
                <?php
                $aid = (int) $assignment->id;
                $sub = $submissions[$aid] ?? null;
                $graded = $sub !== null && !empty($sub->gradedAt);
                $submitted = $sub !== null && !empty($sub->submittedAt);
                ?>
                <tr>
                    <td><?= $assignment->title ?></td>
                    <td class="muted"><?= $assignment->dueAt ?? '—' ?></td>
                    <td class="muted">
                        <?php if ($graded): ?>
                            Graded<?= $sub->gradeScore !== null ? ' (' . $sub->gradeScore . ')' : '' ?>
                        <?php elseif ($submitted): ?>
                            Submitted
                        <?php else: ?>
                            Not submitted
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <a href="/student/assignments/<?= $aid ?>"><?= $submitted ? 'View / edit' : 'Submit' ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
