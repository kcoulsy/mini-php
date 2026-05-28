<?php

declare(strict_types=1);

namespace Framework;

final class View
{
  public function __construct(private readonly string $viewsPath) {}

  /**
   * @param array<string, mixed> $data
   */
  public function render(string $template, array $data = [], ?string $layout = 'layouts/main'): string
  {
    $content = $this->renderFile($template, $data);

    if ($layout === null) {
      return $content;
    }

    return $this->renderFile($layout, array_merge($data, [
      'unsafe_content' => $content,
    ]));
  }

  /**
   * @param array<string, mixed> $data
   */
  private function renderFile(string $template, array $data): string
  {
    $file = $this->viewsPath . '/' . str_replace('.', '/', $template) . '.php';

    if (!is_file($file)) {
      throw new \RuntimeException("View [{$template}] not found.");
    }

    extract($this->prepareData($data), EXTR_SKIP);

    ob_start();
    require $file;

    return (string) ob_get_clean();
  }

  public static function e(?string $value): string
  {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }

  /**
   * Wrap view data so strings escape on output. Keys prefixed with unsafe_ are left raw.
   *
   * @param array<string, mixed> $data
   * @return array<string, mixed>
   */
  private function prepareData(array $data): array
  {
    $prepared = [];

    foreach ($data as $key => $value) {
      $raw = is_string($key) && str_starts_with($key, 'unsafe_');
      $prepared[$key] = $this->escapeValue($value, $raw);
    }

    return $prepared;
  }

  private function escapeValue(mixed $value, bool $raw): mixed
  {
    if ($raw) {
      return $value;
    }

    if ($value instanceof Escaped) {
      return $value;
    }

    if (is_string($value)) {
      return new Escaped($value);
    }

    if (is_array($value)) {
      $escaped = [];

      foreach ($value as $key => $nested) {
        $nestedRaw = is_string($key) && str_starts_with($key, 'unsafe_');
        $escaped[$key] = $this->escapeValue($nested, $nestedRaw);
      }

      return $escaped;
    }

    return $value;
  }

  public static function csrfField(): string
  {
    return '<input type="hidden" name="' . self::e(Csrf::FIELD) . '" value="' . self::e(Csrf::token()) . '">';
  }
}
