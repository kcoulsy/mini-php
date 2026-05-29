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
final class ShowRegister extends Controller
{
    #[Get('/register')]
    public function __invoke(Request $request): Response
    {
        return $this->render('auth/register', [
            'title' => 'Register',
            'errors' => [],
            'old' => ['email' => '', 'name' => ''],
            'formAction' => '/register',
        ]);
    }
}
