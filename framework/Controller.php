<?php

declare(strict_types=1);

namespace Framework;

use Framework\Concerns\Flashes;

abstract class Controller
{
  use Flashes;

  public function __construct(protected readonly View $view) {}

  protected function userId(): int
  {
    $id = Auth::id();

    if ($id === null) {
      throw new \RuntimeException('Authenticated user required.');
    }

    return $id;
  }

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

  /** @return array<string, list<string>> */
  protected function validationErrors(): array
  {
    return \Framework\Validation\ValidationRedirect::pullErrors();
  }

  /** @return array<string, mixed> */
  protected function validationOld(): array
  {
    return \Framework\Validation\ValidationRedirect::pullOld();
  }
}
