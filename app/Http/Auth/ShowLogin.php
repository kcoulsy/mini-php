<?php

declare(strict_types=1);

namespace App\Http\Auth;

use Framework\Controller;
use Framework\Middleware\GuestOnly;
use Framework\Request;
use Framework\Response;
use Framework\Routing\Attributes\Get;
use Framework\Routing\Attributes\Middleware;

#[Middleware([GuestOnly::class])]
final class ShowLogin extends Controller
{
    #[Get('/login')]
    public function __invoke(Request $request): Response
    {
        return $this->render('auth/login', [
            'title' => 'Log in',
            'errors' => [],
            'old' => ['email' => ''],
            'formAction' => '/login',
        ]);
    }
}
