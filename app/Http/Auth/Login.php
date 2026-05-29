<?php

declare(strict_types=1);

namespace App\Http\Auth;

use App\CurrentUser;
use App\Data\LoginData;
use App\Middleware\GuestOnly;
use Framework\Auth;
use Framework\Controller;
use Framework\Response;
use Framework\Routing\Attributes\Get;
use Framework\Routing\Attributes\Middleware;
use Framework\Routing\Attributes\Post;

#[Middleware([GuestOnly::class])]
final class Login extends Controller
{
    #[Get('/login')]
    public function show(): Response
    {
        return $this->render('auth/login', [
            'title' => 'Log in',
            'errors' => $this->validationErrors(),
            'old' => ['email' => (string) ($this->validationOld()['email'] ?? '')],
            'formAction' => '/login',
        ]);
    }

    #[Post('/login')]
    public function login(LoginData $data): Response
    {
        if (!Auth::attempt($data->email, $data->password)) {
            return $this->render('auth/login', [
                'title' => 'Log in',
                'errors' => ['_form' => ['Invalid credentials.']],
                'old' => ['email' => $data->email],
                'formAction' => '/login',
            ]);
        }

        return $this->redirect(Auth::pullIntendedUrl() ?? CurrentUser::homePath());
    }
}
