<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Framework\Middleware\RequireRole;
use Framework\Request;
use Framework\Response;

final class RequireStudent
{
    public function __invoke(Request $request): ?Response
    {
        return RequireRole::allowing(User::ROLE_STUDENT)($request);
    }
}
