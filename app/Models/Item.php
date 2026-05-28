<?php

declare(strict_types=1);

namespace App\Models;

use Framework\Database;
use Framework\FileStorage;

/**
 * @phpstan-type ItemRow array{
 *     id: int|string,
 *     title: string,
 *     description: string,
 *     user_id: int|string,
 *     created_at: string,
 *     updated_at: string
 * }
 */
final class Item
{
  /** @return list<ItemRow> */
  public static function allForUser(int $userId): array
  {
    $stmt = Database::pdo()->prepare(
      'SELECT * FROM items WHERE user_id = :user_id ORDER BY id DESC'
    );
    $stmt->execute(['user_id' => $userId]);

    return $stmt->fetchAll();
  }

  /** @return ItemRow|null */
  public static function findForUser(int $id, int $userId): ?array
  {
    $stmt = Database::pdo()->prepare(
      'SELECT * FROM items WHERE id = :id AND user_id = :user_id LIMIT 1'
    );
    $stmt->execute(['id' => $id, 'user_id' => $userId]);
    $row = $stmt->fetch();

    return $row === false ? null : $row;
  }

  public static function create(string $title, string $description, int $userId): int
  {
    $stmt = Database::pdo()->prepare(
      'INSERT INTO items (title, description, user_id) VALUES (:title, :description, :user_id)'
    );
    $stmt->execute([
      'title' => $title,
      'description' => $description,
      'user_id' => $userId,
    ]);

    return (int) Database::pdo()->lastInsertId();
  }

  public static function update(int $id, int $userId, string $title, string $description): bool
  {
    $stmt = Database::pdo()->prepare(
      'UPDATE items SET title = :title, description = :description, updated_at = datetime(\'now\')
       WHERE id = :id AND user_id = :user_id'
    );

    $stmt->execute([
      'id' => $id,
      'user_id' => $userId,
      'title' => $title,
      'description' => $description,
    ]);

    return $stmt->rowCount() > 0;
  }

  public static function delete(int $id, int $userId, ?FileStorage $storage = null): bool
  {
    if ($storage !== null) {
      ItemAttachment::deleteAllForItem($id, $userId, $storage);
    }

    $stmt = Database::pdo()->prepare('DELETE FROM items WHERE id = :id AND user_id = :user_id');

    $stmt->execute(['id' => $id, 'user_id' => $userId]);

    return $stmt->rowCount() > 0;
  }
}
