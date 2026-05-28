<?php

use Framework\View;

/**
 * @var array<string, list<string>> $errors
 */
?>
<?php if (($errors['_form'] ?? []) !== []): ?>
    <ul class="errors">
        <?php foreach ($errors['_form'] as $error): ?>
            <li><?= $error ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
