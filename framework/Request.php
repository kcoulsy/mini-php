<?php

declare(strict_types=1);

namespace Framework;

final class Request
{
  /**
   * @param array<string, mixed> $query
   * @param array<string, mixed> $body
   * @param array<string, UploadedFile|list<UploadedFile>> $files
   */
  public function __construct(
    private readonly string $method,
    private readonly string $path,
    private readonly array $query,
    private readonly array $body,
    private readonly array $files = [],
  ) {}

  public static function capture(): self
  {
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';

    return self::from(
      $method,
      $path,
      $_GET,
      $method === 'POST' ? $_POST : [],
      self::captureFiles(),
    );
  }

  /**
   * @return array<string, UploadedFile|list<UploadedFile>>
   */
  private static function captureFiles(): array
  {
    $files = [];

    foreach (array_keys($_FILES) as $key) {
      if (!is_string($key)) {
        continue;
      }

      $collection = UploadedFile::collectionFromGlobal($key);

      if ($collection !== []) {
        $files[$key] = $collection;

        continue;
      }

      $single = UploadedFile::fromGlobal($key);

      if ($single !== null) {
        $files[$key] = $single;
      }
    }

    return $files;
  }

  /**
   * Build a request for tests or programmatic dispatch (no superglobals).
   *
   * @param array<string, mixed> $query
   * @param array<string, mixed> $body
   * @param array<string, UploadedFile|list<UploadedFile>> $files
   */
  public static function from(
    string $method,
    string $path,
    array $query = [],
    array $body = [],
    array $files = [],
  ): self {
    $path = rtrim($path, '/') ?: '/';

    return new self(strtoupper($method), $path, $query, $body, $files);
  }

  public function method(): string
  {
    return $this->method;
  }

  public function path(): string
  {
    return $this->path;
  }

  public function query(string $key, mixed $default = null): mixed
  {
    return $this->query[$key] ?? $default;
  }

  public function input(string $key, mixed $default = null): mixed
  {
    return $this->body[$key] ?? $default;
  }

  /** @return array<string, mixed> */
  public function all(): array
  {
    return $this->body;
  }

  public function file(string $key): ?UploadedFile
  {
    $value = $this->files[$key] ?? null;

    return $value instanceof UploadedFile ? $value : null;
  }

  /** @return list<UploadedFile> */
  public function files(string $key): array
  {
    $value = $this->files[$key] ?? null;

    if ($value instanceof UploadedFile) {
      return [$value];
    }

    if (is_array($value)) {
      return $value;
    }

    return [];
  }
}
