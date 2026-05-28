<?php

declare(strict_types=1);

namespace Framework;

final class Request
{
  public function __construct(
    private readonly string $method,
    private readonly string $path,
    /** @var array<string, mixed> */
    private readonly array $query,
    /** @var array<string, mixed> */
    private readonly array $body,
  ) {}

  public static function capture(): self
  {
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';

    return self::from($method, $path, $_GET, $method === 'POST' ? $_POST : []);
  }

  /**
   * Build a request for tests or programmatic dispatch (no superglobals).
   *
   * @param array<string, mixed> $query
   * @param array<string, mixed> $body
   */
  public static function from(
    string $method,
    string $path,
    array $query = [],
    array $body = [],
  ): self {
    $path = rtrim($path, '/') ?: '/';

    return new self(strtoupper($method), $path, $query, $body);
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
}
