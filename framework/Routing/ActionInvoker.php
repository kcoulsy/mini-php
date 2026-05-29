<?php

declare(strict_types=1);

namespace Framework\Routing;

use Framework\Model\Model;
use Framework\Model\ModelMetadata;
use Framework\Request;
use Framework\Response;
use Framework\Validation\DtoFactory;
use Framework\Validation\DtoResult;
use Framework\Validation\ValidationRedirect;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

final class ActionInvoker
{
    /**
     * @param array<string, string> $routeParams
     */
    public function invoke(object $handler, Request $request, array $routeParams): mixed
    {
        if (method_exists($handler, '__invoke')) {
            return $this->invokeMethod($handler, new ReflectionMethod($handler, '__invoke'), $request, $routeParams);
        }

        throw new \RuntimeException('Handler must be invokable.');
    }

    /**
     * @param array<string, string> $routeParams
     */
    public function invokeMethod(
        object $handler,
        ReflectionMethod $method,
        Request $request,
        array $routeParams,
    ): mixed {
        try {
            $args = [];

            foreach ($method->getParameters() as $parameter) {
                $args[] = $this->resolveParameter($handler, $parameter, $request, $routeParams);
            }
        } catch (ValidationFailure $failure) {
            return ValidationRedirect::redirectBack($request, $failure->result);
        }

        return $method->invokeArgs($handler, $args);
    }

    /**
     * @param array<string, string> $routeParams
     */
    private function resolveParameter(
        object $handler,
        ReflectionParameter $parameter,
        Request $request,
        array $routeParams,
    ): mixed {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            $name = $parameter->getName();
            if (isset($routeParams[$name])) {
                return $this->castScalar($routeParams[$name], $type?->getName());
            }

            throw new \RuntimeException("Unable to resolve parameter \${$name}");
        }

        $className = $type->getName();

        if ($className === Request::class) {
            return $request;
        }

        if (is_subclass_of($className, Model::class)) {
            return $this->resolveModel($className, $parameter, $routeParams);
        }

        if (class_exists($className) && $this->isDtoClass($className)) {
            $result = DtoFactory::fromRequest($request, $className);

            if ($result->fails()) {
                throw new ValidationFailure($result);
            }

            return $result->dto;
        }

        throw new \RuntimeException("Unable to resolve parameter \${$parameter->getName()}");
    }

    /**
     * @param class-string<Model> $className
     * @param array<string, string> $routeParams
     */
    private function resolveModel(string $className, ReflectionParameter $parameter, array $routeParams): Model
    {
        $name = $parameter->getName();
        $segment = $routeParams[$name] ?? null;

        if ($segment === null) {
            foreach ($routeParams as $key => $value) {
                if ($key === $name || str_starts_with($key, $name . ':')) {
                    $segment = $value;
                    break;
                }
            }
        }

        if ($segment === null) {
            throw new \RuntimeException("Route parameter for {$name} not found.");
        }

        $column = 'id';
        foreach (array_keys($routeParams) as $key) {
            if ($key === $name . ':id' || preg_match('/^' . preg_quote($name, '/') . ':(.+)$/', $key, $m)) {
                $column = $m[1] ?? 'id';
                break;
            }
        }

        $meta = ModelMetadata::for($className);

        if ($column === $meta->primaryKey || $column === 'id') {
            $model = $className::find($segment);
        } else {
            $model = $className::findBy($column, $segment);
        }

        if ($model === null) {
            throw new ModelNotFoundException($className);
        }

        return $model;
    }

    private function isDtoClass(string $className): bool
    {
        $reflection = new ReflectionClass($className);

        return $reflection->isFinal()
            && $reflection->getNamespaceName() !== ''
            && str_contains($className, '\\Data\\');
    }

    private function castScalar(string $value, ?string $type): int|string
    {
        return match ($type) {
            'int' => (int) $value,
            default => $value,
        };
    }
}

final class ValidationFailure extends \RuntimeException
{
    public function __construct(public readonly DtoResult $result)
    {
        parent::__construct('Validation failed.');
    }
}

final class ModelNotFoundException extends \RuntimeException
{
    /**
     * @param class-string<Model> $model
     */
    public function __construct(public readonly string $model)
    {
        parent::__construct('Model not found.');
    }
}
