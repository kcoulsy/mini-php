<?php

declare(strict_types=1);

namespace Framework;

final class View
{
  /** @var list<string> */
  private static array $scripts = [];

  /** @var array<string, true> */
  private static array $scriptIds = [];

  private static ?string $scriptCaptureId = null;

  private static bool $scriptCaptureDiscard = false;

  private static int $scriptCaptureLevel = 0;

  public function __construct(private readonly string $viewsPath) {}

  /**
   * @param array<string, mixed> $data
   */
  public function render(string $template, array $data = [], ?string $layout = 'layouts/main'): string
  {
    self::resetScripts();

    $content = $this->renderFile($template, $data);

    if ($layout === null) {
      return $content;
    }

    return $this->renderFile($layout, array_merge($data, [
      'unsafe_content' => $content,
      'unsafe_scripts' => self::flushScripts(),
    ]));
  }

  public static function script(string $html, ?string $id = null): void
  {
    $html = trim($html);

    if ($html === '') {
      return;
    }

    if ($id !== null) {
      if (isset(self::$scriptIds[$id])) {
        return;
      }

      self::$scriptIds[$id] = true;
    }

    self::$scripts[] = $html;
  }

  public static function scriptStart(?string $id = null): void
  {
    if ($id !== null && isset(self::$scriptIds[$id])) {
      self::$scriptCaptureId = $id;
      self::$scriptCaptureDiscard = true;
    } else {
      self::$scriptCaptureId = $id;
      self::$scriptCaptureDiscard = false;
    }

    self::$scriptCaptureLevel = ob_get_level();
    ob_start();
  }

  public static function scriptEnd(): void
  {
    if (self::$scriptCaptureLevel === 0) {
      return;
    }

    $html = (string) ob_get_clean();
    self::$scriptCaptureLevel = 0;

    if (self::$scriptCaptureDiscard) {
      self::$scriptCaptureId = null;
      self::$scriptCaptureDiscard = false;

      return;
    }

    $id = self::$scriptCaptureId;
    self::$scriptCaptureId = null;
    self::$scriptCaptureDiscard = false;

    self::script($html, $id);
  }

  public static function flushScripts(): string
  {
    if (self::$scripts === []) {
      return '';
    }

    return implode("\n", self::$scripts);
  }

  private static function resetScripts(): void
  {
    while (self::$scriptCaptureLevel > 0 && ob_get_level() >= self::$scriptCaptureLevel) {
      ob_end_clean();
    }

    self::$scripts = [];
    self::$scriptIds = [];
    self::$scriptCaptureId = null;
    self::$scriptCaptureDiscard = false;
    self::$scriptCaptureLevel = 0;
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

  /**
   * @param array<string, list<string>> $errors
   */
  public static function fieldErrors(array $errors, string $field): void
  {
    $messages = $errors[$field] ?? [];

    if ($messages === []) {
      return;
    }

    echo '<ul class="field-errors">';

    foreach ($messages as $message) {
      $text = $message instanceof Escaped ? $message->raw() : (string) $message;
      echo '<li>' . self::e($text) . '</li>';
    }

    echo '</ul>';
  }

  /**
   * @param array<string, list<string>> $errors
   */
  public static function hasFieldErrors(array $errors, string $field): bool
  {
    return ($errors[$field] ?? []) !== [];
  }
}
