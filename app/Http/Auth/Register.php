<?php

declare(strict_types=1);

namespace App\Http\Auth;

use App\Data\RegisterData;
use App\Models\User;
use Framework\Auth;
use Framework\Controller;
use App\CurrentUser;
use App\Middleware\GuestOnly;
use Framework\Request;
use Framework\Response;
use Framework\Routing\Attributes\Get;
use Framework\Routing\Attributes\Middleware;
use Framework\Routing\Attributes\Post;
use Framework\Validation\ValidationRedirect;

#[Middleware([GuestOnly::class])]
final class Register extends Controller
{
    /** @param array<string, mixed> $authConfig */
    public function __construct(
        \Framework\View $view,
        private readonly array $authConfig = [],
    ) {
        parent::__construct($view);
    }

    #[Get('/register')]
    public function show(): Response
    {
        $old = $this->validationOld();

        return $this->render('auth/register', [
            'title' => 'Register',
            'errors' => $this->validationErrors(),
            'old' => [
                'email' => (string) ($old['email'] ?? ''),
                'name' => (string) ($old['name'] ?? ''),
            ],
            'formAction' => '/register',
        ]);
    }

    #[Post('/register')]
    public function register(RegisterData $data): Response
    {
        $minLength = (int) ($this->authConfig['password_min_length'] ?? 8);

        if (mb_strlen($data->password) < $minLength) {
            ValidationRedirect::storeErrors(
                ['password' => ["Password must be at least {$minLength} characters."]],
                ['email' => $data->email, 'name' => $data->name],
            );

            return $this->redirect('/register');
        }

        $user = User::create([
            'email' => mb_strtolower(trim($data->email)),
            'password' => password_hash($data->password, PASSWORD_DEFAULT),
            'name' => trim($data->name),
            'role' => User::ROLE_STUDENT,
        ]);
        Auth::login($user->id);

        return $this->redirect(CurrentUser::homePath());
    }
}
