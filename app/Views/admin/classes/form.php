<?php

use Framework\View;

/**
 * @var array<string, list<string>> $errors
 * @var array{name: string, join_code: string} $old
 * @var string $formAction
 * @var string $cancelHref
 * @var object|null $schoolClass
 * @var list<int> $teacherIds
 * @var list<object> $teachers
 * @var list<object> $students
 * @var list<object> $allStudents
 */
$isEdit = $schoolClass !== null;
$classId = $isEdit ? (int) $schoolClass->id : 0;
?>

<form method="post" action="<?= $formAction ?>" class="card form-card">
    <?= View::csrfField() ?>
    <?php require dirname(__DIR__, 2) . '/_form_errors.php'; ?>

    <label class="<?= View::hasFieldErrors($errors, 'name') ? 'label-invalid' : '' ?>">
        Name
        <input type="text" name="name" value="<?= $old['name'] ?>" maxlength="120" required>
        <?php View::fieldErrors($errors, 'name'); ?>
    </label>

    <label class="<?= View::hasFieldErrors($errors, 'join_code') ? 'label-invalid' : '' ?>">
        Join code<?= $isEdit ? '' : ' (leave blank to auto-generate)' ?>
        <input type="text" name="join_code" value="<?= $old['join_code'] ?>" maxlength="32">
        <?php View::fieldErrors($errors, 'join_code'); ?>
    </label>

    <fieldset>
        <legend>Teachers</legend>
        <?php foreach ($teachers as $teacher): ?>
            <?php $tid = (int) $teacher->id; ?>
            <label class="checkbox-label">
                <input type="checkbox" name="teacher_ids[]" value="<?= $tid ?>"
                    <?= in_array($tid, $teacherIds, true) ? 'checked' : '' ?>>
                <?= $teacher->name !== '' ? $teacher->name : $teacher->email ?>
                (<?= $teacher->role ?>)
            </label>
        <?php endforeach; ?>
    </fieldset>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save class</button>
        <a href="<?= $cancelHref ?>" class="btn btn-ghost">Cancel</a>
    </div>
</form>

<?php if ($isEdit): ?>
    <h2>Enrolled students</h2>
    <?php if ($students === []): ?>
        <p class="empty">No students enrolled.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?= $student->name !== '' ? $student->name : $student->email ?></td>
                        <td class="actions">
                            <form method="post" action="/admin/classes/<?= $classId ?>/students/<?= (int) $student->id ?>/remove" class="inline-form">
                                <?= View::csrfField() ?>
                                <button type="submit" class="link-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2>Enroll student</h2>
    <form method="post" action="/admin/classes/<?= $classId ?>/enroll" class="card form-card">
        <?= View::csrfField() ?>
        <label>
            Student
            <select name="student_id" required>
                <option value="">Select…</option>
                <?php foreach ($allStudents as $student): ?>
                    <option value="<?= (int) $student->id ?>">
                        <?= $student->name !== '' ? $student->name : $student->email ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enroll</button>
        </div>
    </form>
<?php endif; ?>
