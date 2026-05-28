<?php

declare(strict_types=1);

namespace Framework;

final class Response
{
  public function __construct(
    private string $body = '',
    private int $status = 200,
    /** @var array<string, string> */
    private array $headers = ['Content-Type' => 'text/html; charset=UTF-8'],
  ) {}

  public static function html(string $body, int $status = 200): self
  {
    return new self($body, $status);
  }

  public static function redirect(string $location, int $status = 302): self
  {
    return new self('', $status, [
      'Location' => $location,
      'Content-Type' => 'text/html; charset=UTF-8',
    ]);
  }

  public function status(): int
  {
    return $this->status;
  }

  public function body(): string
  {
    return $this->body;
  }

  /** @return array<string, string> */
  public function headers(): array
  {
    return $this->headers;
  }

  public function header(string $name, ?string $default = null): ?string
  {
    return $this->headers[$name] ?? $default;
  }

  public function send(): void
  {
    http_response_code($this->status);

    foreach ($this->headers as $name => $value) {
      header("{$name}: {$value}");
    }

    echo $this->body;
  }
}
