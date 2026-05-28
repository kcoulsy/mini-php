<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $columns = $pdo->query('PRAGMA table_info(items)')->fetchAll();
    $hasUserId = false;

    foreach ($columns as $column) {
        if (is_array($column) && ($column['name'] ?? '') === 'user_id') {
            $hasUserId = true;
            break;
        }
    }

    if ($hasUserId) {
        return;
    }

    $pdo->exec('ALTER TABLE items ADD COLUMN user_id INTEGER');
    $pdo->exec('DELETE FROM items WHERE user_id IS NULL');
};
