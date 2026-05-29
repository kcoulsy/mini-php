<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Models\User;
use App\Middleware\RequireRole;
use Framework\Request;
use Framework\Response;

final class RequireTeacher
{
    public function __invoke(Request $request): ?Response
    {
        return RequireRole::allowing(User::ROLE_TEACHER, User::ROLE_ADMIN)($request);
    }
}
