<?php

declare(strict_types=1);

namespace Framework;

final class App
{
  private Router $router;
  private View $view;

  /** @param array<string, mixed> $config */
  public function __construct(private readonly array $config)
  {
    $this->router = new Router();
    $this->view = new View($config['base_path'] . '/app/Views');

    Database::connect($config['database']);
  }

  public function router(): Router
  {
    return $this->router;
  }

  public function view(): View
  {
    return $this->view;
  }

  /** @return array<string, mixed> */
  public function config(string $key, mixed $default = null): mixed
  {
    return $this->config[$key] ?? $default;
  }

  public function handle(Request $request): Response
  {
    return $this->router->dispatch($request);
  }

  public function run(): void
  {
    $this->handle(Request::capture())->send();
  }
}
