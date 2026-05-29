<?php

declare(strict_types=1);

namespace App\Middleware;

use App\CurrentUser;
use Framework\Auth;
use Framework\Request;
use Framework\Response;

final class GuestOnly
{
    public function __invoke(Request $request): ?Response
    {
        if (!Auth::check()) {
            return null;
        }

        return Response::redirect(CurrentUser::homePath());
    }
}
