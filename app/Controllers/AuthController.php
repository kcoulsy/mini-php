<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use Framework\Auth;
use Framework\Controller;
use Framework\Request;
use Framework\Response;
use Framework\Session;

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
    [$email, $password, $errors] = $this->validateLogin($request);

    if ($errors !== []) {
      return $this->render('auth/login', $this->loginFormData($errors, $email));
    }

    if (!Auth::attempt($email, $password)) {
      return $this->render('auth/login', $this->loginFormData(
        ['Invalid credentials.'],
        $email,
      ));
    }

    return $this->redirect($this->intendedUrl());
  }

  public function showRegister(Request $request): Response
  {
    return $this->render('auth/register', $this->registerFormData());
  }

  public function register(Request $request): Response
  {
    [$email, $password, $name, $errors] = $this->validateRegister($request);

    if ($errors !== []) {
      return $this->render('auth/register', $this->registerFormData($errors, $email, $name));
    }

    $userId = User::create($email, $password, $name);
    Auth::login($userId);

    return $this->redirect('/items');
  }

  public function logout(Request $request): Response
  {
    Auth::logout();

    return $this->redirect('/login');
  }

  /**
   * @param list<string> $errors
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
   * @param list<string> $errors
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

  /** @return array{0: string, 1: string, 2: list<string>} */
  private function validateLogin(Request $request): array
  {
    $email = trim((string) $request->input('email', ''));
    $password = (string) $request->input('password', '');
    $errors = [];

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors[] = 'A valid email is required.';
    }

    if ($password === '') {
      $errors[] = 'Password is required.';
    }

    return [$email, $password, $errors];
  }

  /** @return array{0: string, 1: string, 2: string, 3: list<string>} */
  private function validateRegister(Request $request): array
  {
    $email = trim((string) $request->input('email', ''));
    $password = (string) $request->input('password', '');
    $confirmation = (string) $request->input('password_confirmation', '');
    $name = trim((string) $request->input('name', ''));
    $errors = [];
    $minLength = (int) ($this->authConfig['password_min_length'] ?? 8);

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors[] = 'A valid email is required.';
    } elseif (User::emailExists($email)) {
      $errors[] = 'Email is already taken.';
    }

    if (mb_strlen($password) < $minLength) {
      $errors[] = "Password must be at least {$minLength} characters.";
    }

    if ($password !== $confirmation) {
      $errors[] = 'Password confirmation does not match.';
    }

    return [$email, $password, $name, $errors];
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

    return '/items';
  }
}
