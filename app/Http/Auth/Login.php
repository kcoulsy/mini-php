<?php

declare(strict_types=1);

namespace App\Http\Auth;

use App\Data\LoginData;
use Framework\Auth;
use Framework\Controller;
use Framework\Middleware\GuestOnly;
use Framework\Request;
use Framework\Response;
use Framework\Routing\Attributes\Get;
use Framework\Routing\Attributes\Middleware;
use Framework\Routing\Attributes\Post;
use Framework\Session;
use Framework\Validation\DtoResult;

#[Middleware([GuestOnly::class])]
final class Login extends Controller
{
    #[Get('/login')]
    public function show(Request $request): Response
    {
        return $this->render('auth/login', $this->formData());
    }

    #[Post('/login')]
    public function login(Request $request, LoginData $data): Response
    {
        if (!Auth::attempt($data->email, $data->password)) {
            return $this->render('auth/login', $this->formData(
                ['_form' => ['Invalid credentials.']],
                $data->email,
            ));
        }

        return $this->redirect($this->intendedUrl());
    }

    public function onValidationFailed(Request $request, DtoResult $result): Response
    {
        return $this->render('auth/login', $this->formData(
            $result->errors,
            (string) ($result->old['email'] ?? ''),
        ));
    }

    /**
     * @param array<string, list<string>> $errors
     * @return array<string, mixed>
     */
    private function formData(array $errors = [], string $email = ''): array
    {
        return [
            'title' => 'Log in',
            'errors' => $errors,
            'old' => ['email' => $email],
            'formAction' => '/login',
        ];
    }

    private function intendedUrl(): string
    {
        $intended = $_SESSION[Session::INTENDED_URL_KEY] ?? null;
        unset($_SESSION[Session::INTENDED_URL_KEY]);

        if (
            is_string($intended)
            && str_starts_with($intended, '/')
            && !str_starts_with($intended, '//')
        ) {
            return $intended;
        }

        return Auth::homePath();
    }
}
