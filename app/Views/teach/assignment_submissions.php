<?php

/**
 * @var array{id: int|string, class_id: int|string, title: string, description: string, due_at: string|null, created_by: int|string, created_at: string, updated_at: string} $assignment
 * @var array{id: int|string, name: string, join_code: string, created_at: string, updated_at: string}|null $schoolClass
 * @var list<array<string, mixed>> $submissions
 * @var array<int, array{id: int|string, email: string, name: string, role: string}|null> $students
 */
?>

<section class="page-head">
    <h1><?= $assignment['title'] ?> — Submissions</h1>
    <?php if ($schoolClass !== null): ?>
        <a href="/teach/classes/<?= (int) $schoolClass['id'] ?>" class="btn btn-ghost">Back</a>
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
                $sid = (int) $submission['student_id'];
                $student = $students[$sid] ?? null;
                ?>
                <tr>
                    <td><?= $student !== null ? ($student['name'] !== '' ? $student['name'] : $student['email']) : 'Unknown' ?></td>
                    <td class="muted"><?= $submission['submitted_at'] ?? '—' ?></td>
                    <td class="muted">
                        <?= !empty($submission['graded_at'])
                            ? ($submission['grade_score'] ?? '—')
                            : 'Not graded' ?>
                    </td>
                    <td class="actions">
                        <a href="/teach/submissions/<?= (int) $submission['id'] ?>">View / grade</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
