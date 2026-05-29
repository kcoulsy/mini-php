<?php

declare(strict_types=1);

namespace App;

use App\Models\User;
use Framework\Auth;

final class CurrentUser
{
    public static function role(): ?string
    {
        return Auth::user()?->role;
    }

    public static function isStudent(): bool
    {
        return self::role() === User::ROLE_STUDENT;
    }

    public static function isTeacher(): bool
    {
        return self::role() === User::ROLE_TEACHER;
    }

    public static function isAdmin(): bool
    {
        return self::role() === User::ROLE_ADMIN;
    }

    public static function homePath(): string
    {
        return match (self::role()) {
            User::ROLE_ADMIN => '/admin',
            User::ROLE_TEACHER => '/teach',
            default => '/student',
        };
    }

    /** @param list<string> $roles */
    public static function hasRole(string ...$roles): bool
    {
        $role = self::role();

        return $role !== null && in_array($role, $roles, true);
    }
}
