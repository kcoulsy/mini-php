<?php

declare(strict_types=1);

namespace Framework;

final class FileStorage
{
  public function __construct(private readonly string $basePath) {}

  public static function fromConfig(array $config): self
  {
    $path = (string) ($config['path'] ?? '');

    if ($path === '') {
      throw new \InvalidArgumentException('Upload storage path is not configured.');
    }

    return new self(rtrim($path, '/\\'));
  }

  public function store(UploadedFile $file, string $directory): string
  {
    $safeDir = trim(str_replace(['..', '\\'], '', $directory), '/');
    $extension = $this->safeExtension($file->extension());
    $filename = $this->generateFilename($extension);
    $relative = $safeDir !== '' ? "{$safeDir}/{$filename}" : $filename;
    $absolute = $this->absolutePath($relative);

    $file->moveTo($absolute);

    return $relative;
  }

  public function delete(string $relativePath): void
  {
    $absolute = $this->absolutePath($relativePath);

    if (is_file($absolute)) {
      unlink($absolute);
    }
  }

  public function absolutePath(string $relativePath): string
  {
    $safe = trim(str_replace(['..', '\\'], '', $relativePath), '/');

    return $this->basePath . '/' . $safe;
  }

  public function exists(string $relativePath): bool
  {
    return is_file($this->absolutePath($relativePath));
  }

  private function safeExtension(string $extension): string
  {
    $extension = strtolower(preg_replace('/[^a-z0-9]+/', '', $extension) ?? '');

    return $extension !== '' ? $extension : 'bin';
  }

  private function generateFilename(string $extension): string
  {
    return bin2hex(random_bytes(16)) . '.' . $extension;
  }
}
