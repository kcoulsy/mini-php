<?php

declare(strict_types=1);

namespace App\Http\Auth;

use App\Data\RegisterData;
use App\Models\User;
use Framework\Auth;
use Framework\Controller;
use Framework\Middleware\GuestOnly;
use Framework\Request;
use Framework\Response;
use Framework\Routing\Attributes\Middleware;
use Framework\Routing\Attributes\Post;
use Framework\Validation\DtoResult;

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

    #[Post('/register')]
    public function __invoke(Request $request, RegisterData $data): Response
    {
        $minLength = (int) ($this->authConfig['password_min_length'] ?? 8);

        if (mb_strlen($data->password) < $minLength) {
            return $this->render('auth/register', $this->formData(
                ['password' => ["Password must be at least {$minLength} characters."]],
                $data->email,
                $data->name,
            ));
        }

        $user = User::create([
            'email' => mb_strtolower(trim($data->email)),
            'password' => password_hash($data->password, PASSWORD_DEFAULT),
            'name' => trim($data->name),
            'role' => User::ROLE_STUDENT,
        ]);
        Auth::login($user->id);

        return $this->redirect(Auth::homePath());
    }

    public function onValidationFailed(Request $request, DtoResult $result): Response
    {
        return $this->render('auth/register', $this->formData(
            $result->errors,
            (string) ($result->old['email'] ?? ''),
            (string) ($result->old['name'] ?? ''),
        ));
    }

    /**
     * @param array<string, list<string>> $errors
     * @return array<string, mixed>
     */
    private function formData(
        array $errors = [],
        string $email = '',
        string $name = '',
    ): array {
        return [
            'title' => 'Register',
            'errors' => $errors,
            'old' => ['email' => $email, 'name' => $name],
            'formAction' => '/register',
        ];
    }
}
