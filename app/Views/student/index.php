<?php

use Framework\View;

/**
 * @var list<array{
 *     id: int|string,
 *     name: string,
 *     join_code: string,
 *     created_at: string,
 *     updated_at: string
 * }> $classes
 */
?>

<section class="page-head">
    <h1>My classes</h1>
</section>

<form method="post" action="/student/classes/join" class="card form-card join-form">
    <?= View::csrfField() ?>
    <label>
        Join a class
        <div class="input-row">
            <input type="text" name="join_code" placeholder="Enter join code" maxlength="32" required>
            <button type="submit" class="btn btn-primary">Join</button>
        </div>
    </label>
</form>

<?php if ($classes === []): ?>
    <p class="empty">You are not enrolled in any classes yet. Use a join code from your teacher.</p>
<?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Class</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($classes as $schoolClass): ?>
                <tr>
                    <td><a href="/student/classes/<?= (int) $schoolClass['id'] ?>"><?= $schoolClass['name'] ?></a></td>
                    <td class="actions"><a href="/student/classes/<?= (int) $schoolClass['id'] ?>">View assignments</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
