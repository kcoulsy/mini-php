<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS class_teacher (
    class_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    PRIMARY KEY (class_id, user_id),
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)
SQL);

    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS class_student (
    class_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    enrolled_at TEXT NOT NULL DEFAULT (datetime('now')),
    PRIMARY KEY (class_id, user_id),
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)
SQL);
};
