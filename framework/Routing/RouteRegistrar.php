<?php

declare(strict_types=1);

namespace Framework\Routing;

use Framework\Request;
use Framework\Response;
use Framework\Router;
use Framework\Routing\Attributes\Get;
use Framework\Routing\Attributes\Middleware;
use Framework\Routing\Attributes\Post;
use Framework\Routing\Attributes\Prefix;
use ReflectionClass;
use ReflectionMethod;

final class RouteRegistrar
{
    public function __construct(
        private readonly Router $router,
        private readonly ActionInvoker $invoker,
    ) {
    }

    public function registerDirectory(string $path, HandlerContainer $container): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            $class = $this->classFromFile($path, $fileInfo->getPathname());

            if (!class_exists($class)) {
                continue;
            }

            $this->registerClass($class, $container);
        }
    }

    /**
     * @param class-string $class
     */
    public function registerClass(string $class, HandlerContainer $container): void
    {
        $reflection = new ReflectionClass($class);

        if ($reflection->isAbstract()) {
            return;
        }

        $prefix = $this->classPrefix($reflection);
        $middleware = $this->classMiddleware($reflection);

        if ($this->hasInvokeRoute($reflection)) {
            $this->registerInvokable($class, $container, $prefix, $middleware);

            return;
        }

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isConstructor() || $method->isStatic()) {
                continue;
            }

            $this->registerMethod($class, $method, $container, $prefix, $middleware);
        }
    }

    /**
     * @param list<class-string|callable> $middleware
     */
    private function registerInvokable(
        string $class,
        HandlerContainer $container,
        string $prefix,
        array $middleware,
    ): void {
        $reflection = new ReflectionClass($class);
        $method = $reflection->getMethod('__invoke');
        $routes = $this->methodRoutes($method);

        foreach ($routes as $route) {
            $pattern = $this->combinePaths($prefix, $route['path']);
            $routeMiddleware = array_merge($middleware, $route['middleware']);
            $handler = function (Request $request, array $routeParams) use ($class, $container): mixed {
                $instance = $container->make($class);

                return $this->dispatch($instance, '__invoke', $request, $routeParams);
            };

            $this->addRoute($route['method'], $pattern, $handler, $routeMiddleware);
        }
    }

    /**
     * @param list<class-string|callable> $middleware
     */
    private function registerMethod(
        string $class,
        ReflectionMethod $method,
        HandlerContainer $container,
        string $prefix,
        array $middleware,
    ): void {
        $routes = $this->methodRoutes($method);

        if ($routes === []) {
            return;
        }

        $methodName = $method->getName();

        foreach ($routes as $route) {
            $pattern = $this->combinePaths($prefix, $route['path']);
            $routeMiddleware = array_merge($middleware, $route['middleware']);
            $handler = function (Request $request, array $routeParams) use ($class, $container, $methodName): mixed {
                $instance = $container->make($class);

                return $this->dispatch($instance, $methodName, $request, $routeParams);
            };

            $this->addRoute($route['method'], $pattern, $handler, $routeMiddleware);
        }
    }

    /**
     * @param array<string, string> $routeParams
     */
    private function dispatch(object $handler, string $method, Request $request, array $routeParams): mixed
    {
        try {
            if ($method === '__invoke') {
                return $this->invoker->invoke($handler, $request, $routeParams);
            }

            return $this->invoker->invokeMethod(
                $handler,
                new ReflectionMethod($handler, $method),
                $request,
                $routeParams,
            );
        } catch (ModelNotFoundException) {
            return Response::html('Not found.', 404);
        }
    }

    /**
     * @param list<class-string|callable> $middleware
     */
    private function addRoute(string $method, string $pattern, callable $handler, array $middleware): void
    {
        if ($method === 'GET') {
            $this->router->get($pattern, $handler, $middleware);
        } elseif ($method === 'POST') {
            $this->router->post($pattern, $handler, $middleware);
        }
    }

    /**
     * @return list<array{method: string, path: string, middleware: list<class-string|callable>}>
     */
    private function methodRoutes(ReflectionMethod $method): array
    {
        $routes = [];
        $methodMiddleware = $this->methodMiddleware($method);

        foreach ($method->getAttributes(Get::class) as $attribute) {
            $routes[] = [
                'method' => 'GET',
                'path' => $attribute->newInstance()->path,
                'middleware' => $methodMiddleware,
            ];
        }

        foreach ($method->getAttributes(Post::class) as $attribute) {
            $routes[] = [
                'method' => 'POST',
                'path' => $attribute->newInstance()->path,
                'middleware' => $methodMiddleware,
            ];
        }

        return $routes;
    }

    private function classPrefix(ReflectionClass $reflection): string
    {
        $attributes = $reflection->getAttributes(Prefix::class);

        if ($attributes === []) {
            return '';
        }

        return rtrim($attributes[0]->newInstance()->path, '/');
    }

    /** @return list<class-string|callable> */
    private function classMiddleware(ReflectionClass $reflection): array
    {
        $middleware = [];

        foreach ($reflection->getAttributes(Middleware::class) as $attribute) {
            array_push($middleware, ...$attribute->newInstance()->middleware);
        }

        return $middleware;
    }

    /** @return list<class-string|callable> */
    private function methodMiddleware(ReflectionMethod $method): array
    {
        $middleware = [];

        foreach ($method->getAttributes(Middleware::class) as $attribute) {
            array_push($middleware, ...$attribute->newInstance()->middleware);
        }

        return $middleware;
    }

    private function hasInvokeRoute(ReflectionClass $reflection): bool
    {
        if (!$reflection->hasMethod('__invoke')) {
            return false;
        }

        return $this->methodRoutes($reflection->getMethod('__invoke')) !== [];
    }

    private function combinePaths(string $prefix, string $path): string
    {
        if ($path === '') {
            return $prefix === '' ? '/' : $prefix;
        }

        if ($prefix === '') {
            return $path;
        }

        return rtrim($prefix, '/') . '/' . ltrim($path, '/');
    }

    private function classFromFile(string $basePath, string $file): string
    {
        $base = str_replace('\\', '/', realpath($basePath) ?: $basePath);
        $normalized = str_replace('\\', '/', $file);
        $relative = ltrim(substr($normalized, strlen($base)), '/');
        $relative = str_replace('/', '\\', $relative);

        return 'App\\Http\\' . substr($relative, 0, -4);
    }
}
