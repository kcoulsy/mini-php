<?php

declare(strict_types=1);

namespace App\Models;

use Framework\Database;
use Framework\FileStorage;
use Framework\UploadedFile;
use Framework\UploadValidator;

/**
 * @phpstan-type ItemAttachmentRow array{
 *     id: int|string,
 *     item_id: int|string,
 *     stored_path: string,
 *     original_name: string,
 *     mime_type: string,
 *     size_bytes: int|string,
 *     created_at: string
 * }
 */
final class ItemAttachment
{
  /** @return list<ItemAttachmentRow> */
  public static function forItem(int $itemId): array
  {
    $stmt = Database::pdo()->prepare(
      'SELECT * FROM item_attachments WHERE item_id = :item_id ORDER BY id ASC'
    );
    $stmt->execute(['item_id' => $itemId]);

    return $stmt->fetchAll();
  }

  /** @return ItemAttachmentRow|null */
  public static function findForUser(int $attachmentId, int $itemId, int $userId): ?array
  {
    $stmt = Database::pdo()->prepare(
      'SELECT a.* FROM item_attachments a
       INNER JOIN items i ON i.id = a.item_id
       WHERE a.id = :attachment_id AND a.item_id = :item_id AND i.user_id = :user_id
       LIMIT 1'
    );
    $stmt->execute([
      'attachment_id' => $attachmentId,
      'item_id' => $itemId,
      'user_id' => $userId,
    ]);
    $row = $stmt->fetch();

    return $row === false ? null : $row;
  }

  /**
   * @param list<UploadedFile> $files
   * @param array<string, mixed> $uploadConfig
   * @return list<string>
   */
  public static function createMany(
    int $itemId,
    array $files,
    int $userId,
    array $uploadConfig,
    FileStorage $storage,
  ): array {
    if ($files === []) {
      return [];
    }

    $errors = UploadValidator::validateMany($uploadConfig, $files);

    if ($errors !== []) {
      return $errors;
    }

    $directory = "{$userId}/{$itemId}";

    foreach ($files as $file) {
      $storedPath = $storage->store($file, $directory);
      $stmt = Database::pdo()->prepare(
        'INSERT INTO item_attachments (item_id, stored_path, original_name, mime_type, size_bytes)
         VALUES (:item_id, :stored_path, :original_name, :mime_type, :size_bytes)'
      );
      $stmt->execute([
        'item_id' => $itemId,
        'stored_path' => $storedPath,
        'original_name' => $file->clientOriginalName(),
        'mime_type' => $file->detectedMimeType(),
        'size_bytes' => $file->size(),
      ]);
    }

    return [];
  }

  /** @param list<int> $ids */
  public static function deleteIds(int $itemId, int $userId, array $ids, FileStorage $storage): void
  {
    if ($ids === []) {
      return;
    }

    $attachments = self::forItem($itemId);
    $allowedIds = [];

    foreach ($attachments as $attachment) {
      if (in_array((int) $attachment['id'], $ids, true)) {
        $allowedIds[] = (int) $attachment['id'];
      }
    }

    if ($allowedIds === []) {
      return;
    }

    if (Item::findForUser($itemId, $userId) === null) {
      return;
    }

    foreach ($allowedIds as $id) {
      $attachment = self::findByIdForItem($id, $itemId);

      if ($attachment === null) {
        continue;
      }

      $storage->delete((string) $attachment['stored_path']);
      $stmt = Database::pdo()->prepare('DELETE FROM item_attachments WHERE id = :id AND item_id = :item_id');
      $stmt->execute(['id' => $id, 'item_id' => $itemId]);
    }
  }

  public static function deleteAllForItem(int $itemId, int $userId, FileStorage $storage): void
  {
    if (Item::findForUser($itemId, $userId) === null) {
      return;
    }

    foreach (self::forItem($itemId) as $attachment) {
      $storage->delete((string) $attachment['stored_path']);
    }

    $stmt = Database::pdo()->prepare('DELETE FROM item_attachments WHERE item_id = :item_id');
    $stmt->execute(['item_id' => $itemId]);
  }

  /** @return ItemAttachmentRow|null */
  private static function findByIdForItem(int $id, int $itemId): ?array
  {
    $stmt = Database::pdo()->prepare(
      'SELECT * FROM item_attachments WHERE id = :id AND item_id = :item_id LIMIT 1'
    );
    $stmt->execute(['id' => $id, 'item_id' => $itemId]);
    $row = $stmt->fetch();

    return $row === false ? null : $row;
  }
}
