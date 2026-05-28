<?php

declare(strict_types=1);

namespace Framework;

final class UploadedFile
{
  private bool $testMode = false;

  /**
   * @param array{name?: string, type?: string, tmp_name?: string, error?: int, size?: int} $file
   */
  private function __construct(private readonly array $file) {}

  public static function fromGlobal(string $key): ?self
  {
    if (!isset($_FILES[$key]) || !is_array($_FILES[$key])) {
      return null;
    }

    $file = $_FILES[$key];

    if (!isset($file['error']) || is_array($file['error'])) {
      return null;
    }

    return new self($file);
  }

  /** @return list<self> */
  public static function collectionFromGlobal(string $key): array
  {
    if (!isset($_FILES[$key]) || !is_array($_FILES[$key])) {
      return [];
    }

    $file = $_FILES[$key];

    if (!isset($file['error']) || !is_array($file['error'])) {
      $single = self::fromGlobal($key);

      return $single !== null ? [$single] : [];
    }

    $files = [];
    $count = count($file['error']);

    for ($i = 0; $i < $count; $i++) {
      $entry = [
        'name' => $file['name'][$i] ?? '',
        'type' => $file['type'][$i] ?? '',
        'tmp_name' => $file['tmp_name'][$i] ?? '',
        'error' => $file['error'][$i] ?? UPLOAD_ERR_NO_FILE,
        'size' => $file['size'][$i] ?? 0,
      ];

      if ((int) $entry['error'] === UPLOAD_ERR_NO_FILE) {
        continue;
      }

      $files[] = new self($entry);
    }

    return $files;
  }

  public static function fake(string $path, string $originalName, string $mime, int $size): self
  {
    $instance = new self([
      'name' => $originalName,
      'type' => $mime,
      'tmp_name' => $path,
      'error' => UPLOAD_ERR_OK,
      'size' => $size,
    ]);
    $instance->testMode = true;

    return $instance;
  }

  public function isValid(): bool
  {
    return $this->error() === UPLOAD_ERR_OK && is_string($this->tmpPath()) && $this->tmpPath() !== '';
  }

  public function error(): int
  {
    return (int) ($this->file['error'] ?? UPLOAD_ERR_NO_FILE);
  }

  public function clientOriginalName(): string
  {
    return (string) ($this->file['name'] ?? '');
  }

  public function clientMimeType(): string
  {
    return (string) ($this->file['type'] ?? '');
  }

  public function size(): int
  {
    return (int) ($this->file['size'] ?? 0);
  }

  public function extension(): string
  {
    $name = $this->clientOriginalName();
    $ext = pathinfo($name, PATHINFO_EXTENSION);

    return strtolower($ext);
  }

  public function tmpPath(): ?string
  {
    $path = $this->file['tmp_name'] ?? null;

    return is_string($path) && $path !== '' ? $path : null;
  }

  public function detectedMimeType(): string
  {
    $path = $this->tmpPath();

    if ($path === null || !is_readable($path)) {
      return '';
    }

    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($path);

    return is_string($mime) ? $mime : '';
  }

  public function moveTo(string $absolutePath): void
  {
    $tmp = $this->tmpPath();

    if ($tmp === null) {
      throw new \RuntimeException('Cannot move upload without a temporary path.');
    }

    $directory = dirname($absolutePath);

    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
      throw new \RuntimeException("Cannot create directory: {$directory}");
    }

    if ($this->testMode) {
      if (!copy($tmp, $absolutePath)) {
        throw new \RuntimeException('Failed to copy test upload.');
      }

      return;
    }

    if (!move_uploaded_file($tmp, $absolutePath)) {
      throw new \RuntimeException('Failed to move uploaded file.');
    }
  }
}
