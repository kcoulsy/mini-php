<?php

declare(strict_types=1);

namespace Framework;

final class Router
{
  /** @var array<int, array{method: string, pattern: string, handler: callable}> */
  private array $routes = [];

  public function get(string $pattern, callable $handler): self
  {
    return $this->add('GET', $pattern, $handler);
  }

  public function post(string $pattern, callable $handler): self
  {
    return $this->add('POST', $pattern, $handler);
  }

  private function add(string $method, string $pattern, callable $handler): self
  {
    $this->routes[] = [
      'method' => $method,
      'pattern' => $pattern,
      'handler' => $handler,
    ];

    return $this;
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
