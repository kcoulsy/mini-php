<?php

use Framework\View;

/**
 * @var string $name
 * @var string $label
 * @var string $autocomplete
 * @var bool $required
 */
$requiredAttr = $required ? ' required' : '';
?>
<label>
    <?= $label ?>
    <span class="password-field">
        <input
            type="password"
            name="<?= View::e($name) ?>"
            autocomplete="<?= View::e($autocomplete) ?>"
            <?= $requiredAttr ?>
        >
        <button
            type="button"
            class="btn btn-ghost password-toggle"
            data-password-toggle
            aria-label="Show password"
            aria-pressed="false"
        >Show</button>
    </span>
</label>
