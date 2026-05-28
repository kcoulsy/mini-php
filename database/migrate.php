<?php

declare(strict_types=1);

use Framework\Database;

$pdo = Database::pdo();

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    name TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
)
SQL);

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    user_id INTEGER NOT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (user_id) REFERENCES users(id)
)
SQL);

$columns = $pdo->query('PRAGMA table_info(items)')->fetchAll();
$hasUserId = false;

foreach ($columns as $column) {
    if (is_array($column) && ($column['name'] ?? '') === 'user_id') {
        $hasUserId = true;
        break;
    }
}

if (!$hasUserId) {
    $pdo->exec('ALTER TABLE items ADD COLUMN user_id INTEGER');
    $pdo->exec('DELETE FROM items WHERE user_id IS NULL');
}
