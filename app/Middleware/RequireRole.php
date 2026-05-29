<?php

declare(strict_types=1);

namespace App\Middleware;

use App\CurrentUser;
use Framework\Auth;
use Framework\Middleware\Authenticate;
use Framework\Request;
use Framework\Response;

final class RequireRole
{
    /**
     * @param list<string> $roles
     * @return callable(Request): ?Response
     */
    public static function allowing(string ...$roles): callable
    {
        return static function (Request $request) use ($roles): ?Response {
            if (!Auth::check()) {
                return (new Authenticate())($request);
            }

            if (!CurrentUser::hasRole(...$roles)) {
                return Response::html('Forbidden.', 403);
            }

            return null;
        };
    }
}
