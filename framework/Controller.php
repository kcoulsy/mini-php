<?php

declare(strict_types=1);

namespace Framework;

use Framework\Concerns\Flashes;

abstract class Controller
{
  use Flashes;

  public function __construct(protected readonly View $view) {}

  /**
   * @param array<string, mixed> $data
   */
  protected function render(string $template, array $data = []): Response
  {
    return Response::html($this->view->render($template, $data));
  }

  protected function redirect(string $path): Response
  {
    return Response::redirect($path);
  }
}
