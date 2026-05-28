<?php

/**
 * @var array{id: int|string, name: string, join_code: string, created_at: string, updated_at: string} $schoolClass
 * @var list<array{
 *     id: int|string,
 *     class_id: int|string,
 *     title: string,
 *     description: string,
 *     due_at: string|null,
 *     created_by: int|string,
 *     created_at: string,
 *     updated_at: string
 * }> $assignments
 * @var array<int, array<string, mixed>|null> $submissions
 */
?>

<section class="page-head">
    <h1><?= $schoolClass['name'] ?></h1>
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
                $aid = (int) $assignment['id'];
                $sub = $submissions[$aid] ?? null;
                $graded = $sub !== null && !empty($sub['graded_at']);
                $submitted = $sub !== null && !empty($sub['submitted_at']);
                ?>
                <tr>
                    <td><?= $assignment['title'] ?></td>
                    <td class="muted"><?= $assignment['due_at'] ?? '—' ?></td>
                    <td class="muted">
                        <?php if ($graded): ?>
                            Graded<?= $sub['grade_score'] !== null ? ' (' . $sub['grade_score'] . ')' : '' ?>
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
