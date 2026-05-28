<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Concerns\Flashes;
use App\Models\User;
use Framework\Auth;
use Framework\Controller;
use Framework\Request;
use Framework\Response;
use Framework\Validator;

final class UserController extends Controller
{
    use Flashes;

    /** @param array<string, mixed> $authConfig */
    public function __construct(
        \Framework\View $view,
        private readonly array $authConfig = [],
    ) {
        parent::__construct($view);
    }

    public function index(Request $request): Response
    {
        return $this->render('admin/users/index', [
            'title' => 'Users',
            'users' => User::all(),
            'flash' => $this->flash(),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->render('admin/users/form', $this->formData());
    }

    public function store(Request $request): Response
    {
        $v = $this->validator($request);

        if ($v->fails()) {
            return $this->render('admin/users/form', $this->formData($v->errors(), $request));
        }

        User::createWithRole(
            (string) $v->get('email'),
            (string) $v->get('password'),
            (string) $v->get('role'),
            (string) $v->get('name'),
        );
        $this->setFlash('User created.');

        return $this->redirect('/admin/users');
    }

    public function edit(Request $request, string $id): Response
    {
        $user = User::find((int) $id);

        if ($user === null) {
            return Response::html('User not found.', 404);
        }

        return $this->render('admin/users/form', $this->formData([], null, $user));
    }

    public function update(Request $request, string $id): Response
    {
        $userId = (int) $id;
        $user = User::find($userId);

        if ($user === null) {
            return Response::html('User not found.', 404);
        }

        $v = $this->validator($request, $userId, true);

        if ($v->fails()) {
            return $this->render('admin/users/form', $this->formData($v->errors(), $request, $user));
        }

        $newRole = (string) $v->get('role');

        if (
            ($user['role'] ?? '') === User::ROLE_ADMIN
            && $newRole !== User::ROLE_ADMIN
            && User::countAdmins() <= 1
        ) {
            return $this->render('admin/users/form', $this->formData(
                ['_form' => ['Cannot remove the last admin.']],
                $request,
                $user,
            ));
        }

        $password = (string) $v->get('password');

        User::update(
            $userId,
            (string) $v->get('email'),
            (string) $v->get('name'),
            $newRole,
            $password !== '' ? $password : null,
        );
        $this->setFlash('User updated.');

        return $this->redirect('/admin/users');
    }

    public function destroy(Request $request, string $id): Response
    {
        $userId = (int) $id;
        $user = User::find($userId);

        if ($user === null) {
            return Response::html('User not found.', 404);
        }

        if ($userId === Auth::id()) {
            $this->setFlash('You cannot delete your own account.');

            return $this->redirect('/admin/users');
        }

        if (($user['role'] ?? '') === User::ROLE_ADMIN && User::countAdmins() <= 1) {
            $this->setFlash('Cannot delete the last admin.');

            return $this->redirect('/admin/users');
        }

        User::delete($userId);
        $this->setFlash('User deleted.');

        return $this->redirect('/admin/users');
    }

    private function validator(Request $request, ?int $exceptId = null, bool $isEdit = false): Validator
    {
        $minLength = (int) ($this->authConfig['password_min_length'] ?? 8);
        $rules = [
            'email' => [
                'trim',
                'required',
                'email',
                static function (mixed $value) use ($exceptId): ?string {
                    if (!is_string($value)) {
                        return null;
                    }

                    $existing = User::findByEmail($value);

                    if ($existing === null) {
                        return null;
                    }

                    if ($exceptId !== null && (int) $existing['id'] === $exceptId) {
                        return null;
                    }

                    return 'Email is already taken.';
                },
            ],
            'name' => 'trim',
            'role' => [
                'required',
                static function (mixed $value): ?string {
                    return is_string($value) && in_array($value, User::ROLES, true)
                        ? null
                        : 'Invalid role.';
                },
            ],
        ];

        if ($isEdit) {
            $rules['password'] = "trim|min:{$minLength}";
        } else {
            $rules['password'] = "required|min:{$minLength}";
        }

        return Validator::make($request->all(), $rules, [
            'email.required' => 'Email is required.',
            'password.required' => 'Password is required.',
            'password.min' => "Password must be at least {$minLength} characters.",
        ]);
    }

    /**
     * @param array<string, list<string>> $errors
     * @param array<string, mixed>|null $user
     * @return array<string, mixed>
     */
    private function formData(
        array $errors = [],
        ?Request $request = null,
        ?array $user = null,
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
                'email' => (string) $user['email'],
                'name' => (string) $user['name'],
                'role' => (string) $user['role'],
            ];
        } else {
            $old = ['email' => '', 'name' => '', 'role' => User::ROLE_TEACHER];
        }

        return [
            'title' => $isEdit ? 'Edit user' : 'New user',
            'errors' => $errors,
            'old' => $old,
            'user' => $user,
            'formAction' => $isEdit ? '/admin/users/' . $user['id'] : '/admin/users',
            'cancelHref' => '/admin/users',
            'roles' => User::ROLES,
        ];
    }
}
