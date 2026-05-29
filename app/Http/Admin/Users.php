<?php

declare(strict_types=1);

namespace App\Http\Admin;

use App\Data\StoreUserData;
use App\Data\UpdateUserData;
use App\Models\User;
use Framework\Auth;
use Framework\Controller;
use Framework\Middleware\Authenticate;
use Framework\Middleware\RequireAdmin;
use Framework\Request;
use Framework\Response;
use Framework\Routing\Attributes\Get;
use Framework\Routing\Attributes\Middleware;
use Framework\Routing\Attributes\Post;
use Framework\Routing\Attributes\Prefix;
use Framework\Validation\DtoResult;

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
        return $this->render('admin/users/form', $this->formData([], null, $user));
    }

    #[Post('/users/{user}')]
    public function update(Request $request, User $user, UpdateUserData $data): Response
    {
        $existing = User::findBy('email', mb_strtolower(trim($data->email)));
        if ($existing !== null && $existing->id !== $user->id) {
            return $this->render('admin/users/form', $this->formData(
                ['email' => ['Email is already taken.']],
                $request,
                $user,
            ));
        }

        if (
            $user->role === User::ROLE_ADMIN
            && $data->role !== User::ROLE_ADMIN
            && $this->adminCount() <= 1
        ) {
            return $this->render('admin/users/form', $this->formData(
                ['_form' => ['Cannot remove the last admin.']],
                $request,
                $user,
            ));
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

    public function onValidationFailed(Request $request, DtoResult $result): Response
    {
        $path = $request->path();

        if ($path === '/admin/users') {
            return $this->render('admin/users/form', $this->formData($this->withPasswordMessage($result), $request));
        }

        if (preg_match('#^/admin/users/(\d+)$#', $path, $m)) {
            $user = User::find((int) $m[1]);
            if ($user === null) {
                return Response::html('User not found.', 404);
            }

            return $this->render('admin/users/form', $this->formData(
                $this->withPasswordMessage($result),
                $request,
                $user,
            ));
        }

        return Response::html('Validation failed.', 422);
    }

    /**
     * @param array<string, list<string>> $errors
     * @return array<string, mixed>
     */
    private function formData(
        array $errors = [],
        ?Request $request = null,
        ?User $user = null,
    ): array {
        $isEdit = $user !== null;

        if ($request !== null) {
            $old = [
                'email' => (string) $request->input('email', ''),
                'name' => (string) $request->input('name', ''),
                'role' => (string) $request->input('role', User::ROLE_STUDENT),
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
     * @return array<string, list<string>>
     */
    private function withPasswordMessage(DtoResult $result): array
    {
        $errors = $result->errors;
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
