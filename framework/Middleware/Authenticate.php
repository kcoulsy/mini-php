<?php

declare(strict_types=1);

namespace Framework\Middleware;

use Framework\Auth;
use Framework\Request;
use Framework\Response;
use Framework\Session;

final class Authenticate
{
    public function __invoke(Request $request): ?Response
    {
        if (Auth::check()) {
            return null;
        }

        $_SESSION[Session::INTENDED_URL_KEY] = $request->path();

        return Response::redirect('/login');
    }
}
