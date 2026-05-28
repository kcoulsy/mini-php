<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
  $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS item_attachments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  item_id INTEGER NOT NULL,
  stored_path TEXT NOT NULL,
  original_name TEXT NOT NULL,
  mime_type TEXT NOT NULL,
  size_bytes INTEGER NOT NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
)
SQL);
  $pdo->exec('CREATE INDEX IF NOT EXISTS item_attachments_item_id_idx ON item_attachments (item_id)');
};
