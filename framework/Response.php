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
    if (!self::isSafeRedirect($location)) {
      $location = '/';
    }

    return new self('', $status, [
      'Location' => $location,
      'Content-Type' => 'text/html; charset=UTF-8',
    ]);
  }

  public static function download(string $absolutePath, string $downloadName, string $mime): self
  {
    if (!is_readable($absolutePath)) {
      return new self('File not found.', 404);
    }

    $body = (string) file_get_contents($absolutePath);
    $safeName = self::safeDownloadFilename($downloadName);

    return new self($body, 200, [
      'Content-Type' => $mime,
      'Content-Disposition' => 'attachment; filename="' . $safeName . '"',
      'Content-Length' => (string) strlen($body),
    ]);
  }

  private static function safeDownloadFilename(string $name): string
  {
    $name = basename(str_replace(["\0", '"', "\r", "\n"], '', $name));
    $ascii = preg_replace('/[^\x20-\x7E]+/', '_', $name) ?? 'download';

    return $ascii !== '' ? $ascii : 'download';
  }

  public static function isSafeRedirect(string $location): bool
  {
    if ($location === '' || $location[0] !== '/') {
      return false;
    }

    return !str_starts_with($location, '//');
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

  /**
   * @param array<string, string> $headers
   */
  public function withHeaders(array $headers): self
  {
    return new self($this->body, $this->status, array_merge($this->headers, $headers));
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
