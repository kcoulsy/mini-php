<?php

declare(strict_types=1);

namespace Framework\Middleware;

use Framework\Auth;
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

            if (!Auth::hasRole(...$roles)) {
                return Response::html('Forbidden.', 403);
            }

            return null;
        };
    }
}
