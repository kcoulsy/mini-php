<?php

declare(strict_types=1);

namespace App\Http\Admin;

use App\Data\StoreUserData;
use App\Data\UpdateUserData;
use App\Models\User;
use Framework\Auth;
use Framework\Controller;
use Framework\Middleware\Authenticate;
use App\Middleware\RequireAdmin;
use Framework\Request;
use Framework\Response;
use Framework\Routing\Attributes\Get;
use Framework\Routing\Attributes\Middleware;
use Framework\Routing\Attributes\Post;
use Framework\Routing\Attributes\Prefix;
use Framework\Validation\ValidationRedirect;

#[Prefix('/admin')]
#[Middleware([Authenticate::class, RequireAdmin::class])]
final class Users extends Controller
{
    /** @param array<string, mixed> $authConfig */
    public function __construct(
        \Framework\View $view,
        private readonly array $authConfig = [],
    ) {
        parent::__construct($view);
    }

    #[Get('/users')]
    public function index(Request $request): Response
    {
        return $this->render('admin/users/index', [
            'title' => 'Users',
            'users' => User::query()->orderBy('id', 'asc')->get(),
            'flash' => $this->flash(),
        ]);
    }

    #[Get('/users/create')]
    public function create(Request $request): Response
    {
        return $this->render('admin/users/form', $this->formData());
    }

    #[Post('/users')]
    public function store(Request $request, StoreUserData $data): Response
    {
        User::create([
            'email' => mb_strtolower(trim($data->email)),
            'password' => password_hash($data->password, PASSWORD_DEFAULT),
            'name' => trim($data->name),
            'role' => $data->role,
        ]);
        $this->setFlash('User created.');

        return $this->redirect('/admin/users');
    }

    #[Get('/users/{user}/edit')]
    public function edit(Request $request, User $user): Response
    {
        return $this->render('admin/users/form', $this->formData($user));
    }

    #[Post('/users/{user}')]
    public function update(Request $request, User $user, UpdateUserData $data): Response
    {
        $existing = User::findBy('email', mb_strtolower(trim($data->email)));
        if ($existing !== null && $existing->id !== $user->id) {
            ValidationRedirect::storeRequestErrors($request, ['email' => ['Email is already taken.']]);

            return $this->redirect('/admin/users/' . $user->id . '/edit');
        }

        if (
            $user->role === User::ROLE_ADMIN
            && $data->role !== User::ROLE_ADMIN
            && $this->adminCount() <= 1
        ) {
            ValidationRedirect::storeRequestErrors($request, ['_form' => ['Cannot remove the last admin.']]);

            return $this->redirect('/admin/users/' . $user->id . '/edit');
        }

        $password = $data->password;
        $user->email = mb_strtolower(trim($data->email));
        $user->name = trim($data->name);
        $user->role = $data->role;

        if ($password !== null && $password !== '') {
            $user->password = password_hash($password, PASSWORD_DEFAULT);
        }

        $user->save();
        $this->setFlash('User updated.');

        return $this->redirect('/admin/users');
    }

    #[Post('/users/{user}/delete')]
    public function destroy(Request $request, User $user): Response
    {
        if ($user->id === Auth::id()) {
            $this->setFlash('You cannot delete your own account.');

            return $this->redirect('/admin/users');
        }

        if ($user->role === User::ROLE_ADMIN && $this->adminCount() <= 1) {
            $this->setFlash('Cannot delete the last admin.');

            return $this->redirect('/admin/users');
        }

        $user->delete();
        $this->setFlash('User deleted.');

        return $this->redirect('/admin/users');
    }

    /**
     * @param array<string, list<string>> $errors
     * @return array<string, mixed>
     */
    private function formData(?User $user = null, array $errors = []): array
    {
        $isEdit = $user !== null;
        $sessionOld = $this->validationOld();
        $sessionErrors = $this->validationErrors();

        if ($errors === [] && $sessionErrors !== []) {
            $errors = $this->normalizePasswordErrors($sessionErrors);
        }

        if ($sessionOld !== []) {
            $old = [
                'email' => (string) ($sessionOld['email'] ?? ''),
                'name' => (string) ($sessionOld['name'] ?? ''),
                'role' => (string) ($sessionOld['role'] ?? User::ROLE_STUDENT),
            ];
        } elseif ($isEdit) {
            $old = [
                'email' => $user->email,
                'name' => $user->name,
                'role' => $user->role,
            ];
        } else {
            $old = ['email' => '', 'name' => '', 'role' => User::ROLE_TEACHER];
        }

        return [
            'title' => $isEdit ? 'Edit user' : 'New user',
            'errors' => $errors,
            'old' => $old,
            'user' => $user,
            'formAction' => $isEdit ? '/admin/users/' . $user->id : '/admin/users',
            'cancelHref' => '/admin/users',
            'roles' => User::ROLES,
        ];
    }

    /**
     * @param array<string, list<string>> $errors
     * @return array<string, list<string>>
     */
    private function normalizePasswordErrors(array $errors): array
    {
        $minLength = (int) ($this->authConfig['password_min_length'] ?? 8);

        if (isset($errors['password'])) {
            foreach ($errors['password'] as $idx => $message) {
                if (str_contains($message, 'at least')) {
                    $errors['password'][$idx] = "Password must be at least {$minLength} characters.";
                }
            }
        }

        return $errors;
    }

    private function adminCount(): int
    {
        return User::query()->where('role', User::ROLE_ADMIN)->get()->count();
    }
}
