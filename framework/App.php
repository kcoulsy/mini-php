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
    if ($this->config('security.csrf', true) && $request->method() === 'POST') {
      if (!Csrf::validate($request)) {
        return $this->secure(Response::html('Invalid or missing CSRF token.', 403));
      }
    }

    return $this->secure($this->router->dispatch($request));
  }

  private function secure(Response $response): Response
  {
    if ($this->config('security.headers', true) === false) {
      return $response;
    }

    /** @var array<string, mixed> $security */
    $security = $this->config('security', []);

    return HttpSecurity::apply($response, $security);
  }

  public function run(): void
  {
    $this->handle(Request::capture())->send();
  }
}
