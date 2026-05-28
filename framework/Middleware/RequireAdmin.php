<?php

declare(strict_types=1);

namespace Framework\Middleware;

use App\Models\User;
use Framework\Auth;
use Framework\Request;
use Framework\Response;

final class RequireAdmin
{
    public function __invoke(Request $request): ?Response
    {
        if (!Auth::check()) {
            return (new Authenticate())($request);
        }

        if (!Auth::isAdmin()) {
            return Response::html('Forbidden.', 403);
        }

        return null;
    }
}
