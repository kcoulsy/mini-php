<?php

/**
 * @var object $assignment
 * @var object|null $schoolClass
 * @var list<object> $submissions
 * @var array<int, array{id: int|string, email: string, name: string, role: string}|null> $students
 */
?>

<section class="page-head">
    <h1><?= $assignment->title ?> — Submissions</h1>
    <?php if ($schoolClass !== null): ?>
        <a href="/teach/classes/<?= (int) $schoolClass->id ?>" class="btn btn-ghost">Back</a>
    <?php endif; ?>
</section>

<?php if ($submissions === []): ?>
    <p class="empty">No submissions yet.</p>
<?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Submitted</th>
                <th>Grade</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($submissions as $submission): ?>
                <?php
                $sid = (int) $submission->studentId;
                $student = $students[$sid] ?? null;
                ?>
                <tr>
                    <td><?= $student !== null ? ($student['name'] !== '' ? $student['name'] : $student['email']) : 'Unknown' ?></td>
                    <td class="muted"><?= $submission->submittedAt ?? '—' ?></td>
                    <td class="muted">
                        <?= !empty($submission->gradedAt)
                            ? ($submission->gradeScore ?? '—')
                            : 'Not graded' ?>
                    </td>
                    <td class="actions">
                        <a href="/teach/submissions/<?= (int) $submission->id ?>">View / grade</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
