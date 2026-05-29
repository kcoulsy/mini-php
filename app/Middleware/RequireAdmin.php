<?php

declare(strict_types=1);

namespace App\Middleware;

use App\CurrentUser;
use Framework\Auth;
use Framework\Middleware\Authenticate;
use Framework\Request;
use Framework\Response;

final class RequireAdmin
{
    public function __invoke(Request $request): ?Response
    {
        if (!Auth::check()) {
            return (new Authenticate())($request);
        }

        if (!CurrentUser::isAdmin()) {
            return Response::html('Forbidden.', 403);
        }

        return null;
    }
}
