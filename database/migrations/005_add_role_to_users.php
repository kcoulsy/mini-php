<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $columns = $pdo->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_ASSOC);
    $hasRole = false;

    foreach ($columns as $column) {
        if (($column['name'] ?? '') === 'role') {
            $hasRole = true;
            break;
        }
    }

    if ($hasRole) {
        return;
    }

    $pdo->exec("ALTER TABLE users ADD COLUMN role TEXT NOT NULL DEFAULT 'student'");
};
