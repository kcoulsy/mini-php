<?php

declare(strict_types=1);

namespace Framework;

use PDO;
use PDOException;

final class Database
{
  private static ?PDO $connection = null;

  /** @param array{driver: string, path: string} $config */
  public static function connect(array $config): PDO
  {
    if (self::$connection instanceof PDO) {
      return self::$connection;
    }

    $dir = dirname($config['path']);

    if (!is_dir($dir)) {
      mkdir($dir, 0775, true);
    }

    $dsn = 'sqlite:' . $config['path'];

    try {
      self::$connection = new PDO($dsn, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      ]);
    } catch (PDOException $e) {
      throw new \RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
    }

    return self::$connection;
  }

  public static function pdo(): PDO
  {
    if (!self::$connection instanceof PDO) {
      throw new \RuntimeException('Database not connected. Call Database::connect() first.');
    }

    return self::$connection;
  }

  public static function disconnect(): void
  {
    self::$connection = null;
  }
}
