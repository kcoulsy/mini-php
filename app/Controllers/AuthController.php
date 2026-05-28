<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use Framework\Auth;
use Framework\Controller;
use Framework\Request;
use Framework\Response;
use Framework\Session;
use Framework\Validator;

final class AuthController extends Controller
{
  /** @param array<string, mixed> $authConfig */
  public function __construct(
    \Framework\View $view,
    private readonly array $authConfig = [],
  ) {
    parent::__construct($view);
  }

  public function showLogin(Request $request): Response
  {
    return $this->render('auth/login', $this->loginFormData());
  }

  public function login(Request $request): Response
  {
    $v = Validator::make($request->all(), [
      'email' => 'trim|required|email',
      'password' => 'required',
    ], [
      'email.required' => 'A valid email is required.',
      'email.email' => 'A valid email is required.',
      'password.required' => 'Password is required.',
    ]);

    if ($v->fails()) {
      return $this->render('auth/login', $this->loginFormData($v->errors(), (string) $v->get('email')));
    }

    $email = (string) $v->get('email');
    $password = (string) $v->get('password');

    if (!Auth::attempt($email, $password)) {
      $errors = $v->errors();
      $errors['_form'][] = 'Invalid credentials.';

      return $this->render('auth/login', $this->loginFormData($errors, $email));
    }

    return $this->redirect($this->intendedUrl());
  }

  public function showRegister(Request $request): Response
  {
    return $this->render('auth/register', $this->registerFormData());
  }

  public function register(Request $request): Response
  {
    $minLength = (int) ($this->authConfig['password_min_length'] ?? 8);
    $v = Validator::make($request->all(), [
      'email' => [
        'trim',
        'required',
        'email',
        static fn (mixed $value): ?string => is_string($value) && User::emailExists($value)
          ? 'Email is already taken.'
          : null,
      ],
      'password' => "required|min:{$minLength}",
      'password_confirmation' => 'confirmed',
      'name' => 'trim',
    ], [
      'email.required' => 'A valid email is required.',
      'email.email' => 'A valid email is required.',
      'password.min' => "Password must be at least {$minLength} characters.",
      'password_confirmation.confirmed' => 'Password confirmation does not match.',
    ]);

    if ($v->fails()) {
      return $this->render('auth/register', $this->registerFormData(
        $v->errors(),
        (string) $v->get('email'),
        (string) $v->get('name'),
      ));
    }

    $userId = User::createStudent(
      (string) $v->get('email'),
      (string) $v->get('password'),
      (string) $v->get('name'),
    );
    Auth::login($userId);

    return $this->redirect(Auth::homePath());
  }

  public function logout(Request $request): Response
  {
    Auth::logout();

    return $this->redirect('/login');
  }

  /**
   * @param array<string, list<string>> $errors
   * @return array<string, mixed>
   */
  private function loginFormData(array $errors = [], string $email = ''): array
  {
    return [
      'title' => 'Log in',
      'errors' => $errors,
      'old' => ['email' => $email],
      'formAction' => '/login',
    ];
  }

  /**
   * @param array<string, list<string>> $errors
   * @return array<string, mixed>
   */
  private function registerFormData(
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
