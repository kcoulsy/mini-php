<?php

declare(strict_types=1);

namespace App\Http\Auth;

use Framework\Auth;
use Framework\Controller;
use Framework\Middleware\Authenticate;
use Framework\Request;
use Framework\Response;
use Framework\Routing\Attributes\Middleware;
use Framework\Routing\Attributes\Post;

#[Middleware([Authenticate::class])]
final class Logout extends Controller
{
    #[Post('/logout')]
    public function __invoke(Request $request): Response
    {
        Auth::logout();

        return $this->redirect('/login');
    }
}
