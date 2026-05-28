<?php

declare(strict_types=1);

namespace App\Controllers\Concerns;

trait Flashes
{
    protected function setFlash(string $message): void
    {
        $_SESSION['flash'] = $message;
    }

    protected function flash(): ?string
    {
        $message = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        return is_string($message) ? $message : null;
    }
}
