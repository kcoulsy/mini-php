<?php

declare(strict_types=1);

namespace Framework;

final class Router
{
  /** @var array<int, array{method: string, pattern: string, handler: callable, middleware: list<callable(Request): ?Response>}> */
  private array $routes = [];

  /**
   * @param list<class-string|callable(Request): ?Response> $middleware
   */
  public function get(string $pattern, callable $handler, array $middleware = []): self
  {
    return $this->add('GET', $pattern, $handler, $middleware);
  }

  /**
   * @param list<class-string|callable(Request): ?Response> $middleware
   */
  public function post(string $pattern, callable $handler, array $middleware = []): self
  {
    return $this->add('POST', $pattern, $handler, $middleware);
  }

  /**
   * @param list<class-string|callable(Request): ?Response> $middleware
   */
  private function add(string $method, string $pattern, callable $handler, array $middleware): self
  {
    $this->routes[] = [
      'method' => $method,
      'pattern' => $pattern,
      'handler' => $handler,
      'middleware' => $this->resolveMiddleware($middleware),
    ];

    return $this;
  }

  /**
   * @param list<class-string|callable(Request): ?Response> $middleware
   * @return list<callable(Request): ?Response>
   */
  private function resolveMiddleware(array $middleware): array
  {
    $resolved = [];

    foreach ($middleware as $entry) {
      if (is_string($entry)) {
        $resolved[] = new $entry();
        continue;
      }

      $resolved[] = $entry;
    }

    return $resolved;
  }

  public function dispatch(Request $request): Response
  {
    foreach ($this->routes as $route) {
      if ($route['method'] !== $request->method()) {
        continue;
      }

      $params = $this->match($route['pattern'], $request->path());

      if ($params === null) {
        continue;
      }

      foreach ($route['middleware'] as $middleware) {
        $result = $middleware($request);

        if ($result instanceof Response) {
          return $this->normalize($result);
        }
      }

      $result = ($route['handler'])($request, ...array_values($params));

      return $this->normalize($result);
    }

    return Response::html($this->notFoundHtml(), 404);
  }

  /** @return array<string, string>|null */
  private function match(string $pattern, string $path): ?array
  {
    $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
    $regex = '#^' . $regex . '$#';

    if (!preg_match($regex, $path, $matches)) {
      return null;
    }

    $params = [];

    foreach ($matches as $key => $value) {
      if (is_string($key)) {
        $params[$key] = $value;
      }
    }

    return $params;
  }

  private function normalize(mixed $result): Response
  {
    if ($result instanceof Response) {
      return $result;
    }

    if (is_string($result)) {
      return Response::html($result);
    }

    throw new \RuntimeException('Route handler must return Response or string.');
  }

  private function notFoundHtml(): string
  {
    return '<!DOCTYPE html><html><head><title>404</title></head><body><h1>404 Not Found</h1></body></html>';
  }
}
