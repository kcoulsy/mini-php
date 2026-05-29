<?php

declare(strict_types=1);

namespace Framework\Routing;

use Framework\View;

final class HandlerContainer
{
    /**
     * @param array<string, mixed> $authConfig
     * @param array<string, mixed> $uploadConfig
     */
    public function __construct(
        private readonly View $view,
        private readonly array $authConfig = [],
        private readonly array $uploadConfig = [],
    ) {
    }

    public function make(string $class): object
    {
        if (is_subclass_of($class, \Framework\Controller::class)) {
            if ($this->needsUploadConfig($class)) {
                return new $class($this->view, $this->uploadConfig);
            }

            if ($this->needsAuthConfig($class)) {
                return new $class($this->view, $this->authConfig);
            }

            return new $class($this->view);
        }

        return new $class($this->view);
    }

    private function needsAuthConfig(string $class): bool
    {
        return str_contains($class, '\\Auth\\')
            || str_contains($class, 'Admin\\UserRoutes')
            || str_contains($class, 'Admin\\Users\\')
            || str_contains($class, 'AdminUserRoutes');
    }

    private function needsUploadConfig(string $class): bool
    {
        return str_contains($class, 'Student')
            || str_contains($class, 'Teach')
            || str_contains($class, 'Admin\\SubmissionRoutes')
            || str_contains($class, 'Admin\\Submissions')
            || str_contains($class, 'AdminSubmissionRoutes');
    }
}
