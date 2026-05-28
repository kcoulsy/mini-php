<?php

declare(strict_types=1);

namespace App\Models;

use Framework\Database;

final class Item
{
  /** @return list<array<string, mixed>> */
  public static function all(): array
  {
    $stmt = Database::pdo()->query('SELECT * FROM items ORDER BY id DESC');

    return $stmt->fetchAll();
  }

  public static function find(int $id): ?array
  {
    $stmt = Database::pdo()->prepare('SELECT * FROM items WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    return $row === false ? null : $row;
  }

  public static function create(string $title, string $description): int
  {
    $stmt = Database::pdo()->prepare(
      'INSERT INTO items (title, description) VALUES (:title, :description)'
    );
    $stmt->execute([
      'title' => $title,
      'description' => $description,
    ]);

    return (int) Database::pdo()->lastInsertId();
  }

  public static function update(int $id, string $title, string $description): bool
  {
    $stmt = Database::pdo()->prepare(
      'UPDATE items SET title = :title, description = :description, updated_at = datetime(\'now\') WHERE id = :id'
    );

    return $stmt->execute([
      'id' => $id,
      'title' => $title,
      'description' => $description,
    ]);
  }

  public static function delete(int $id): bool
  {
    $stmt = Database::pdo()->prepare('DELETE FROM items WHERE id = :id');

    return $stmt->execute(['id' => $id]);
  }
}
