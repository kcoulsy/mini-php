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
      'content' => $content,
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

    extract($data, EXTR_SKIP);

    ob_start();
    require $file;

    return (string) ob_get_clean();
  }

  public static function e(?string $value): string
  {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }
}
