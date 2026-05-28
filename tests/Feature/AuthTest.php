<?php

declare(strict_types=1);

namespace Tests\Feature;

use Framework\Session;
use Tests\Support\ApplicationTestCase;

final class AuthTest extends ApplicationTestCase
{
    public function testGuestIsRedirectedFromStudentToLogin(): void
    {
        $response = $this->get('/student');

        $this->assertRedirect($response, '/login');
    }

    public function testRegisterCreatesStudentAndRedirectsToStudentHome(): void
    {
        $response = $this->post('/register', [
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $this->assertRedirect($response, '/student');

        $home = $this->get('/student');
        $this->assertOk($home);
        $this->assertSee($home, 'My classes');
    }

    public function testLoginWithValidCredentials(): void
    {
        $this->createStudent('login@example.com', 'password123');

        $response = $this->post('/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $this->assertRedirect($response, '/student');
    }

    public function testLoginWithInvalidCredentialsShowsGenericError(): void
    {
        $this->createStudent('login@example.com', 'password123');

        $response = $this->post('/login', [
            'email' => 'login@example.com',
            'password' => 'wrong',
        ]);

        $this->assertOk($response);
        $this->assertSee($response, 'Invalid credentials.');
    }

    public function testAuthenticatedStudentRedirectedFromLogin(): void
    {
        $userId = $this->createStudent();
        $this->actingAs($userId);

        $response = $this->get('/login');

        $this->assertRedirect($response, '/student');
    }

    public function testLogoutClearsSession(): void
    {
        $userId = $this->createStudent();
        $this->actingAs($userId);

        $response = $this->post('/logout');

        $this->assertRedirect($response, '/login');
        $this->assertFalse(isset($_SESSION[Session::USER_KEY]));

        $home = $this->get('/student');
        $this->assertRedirect($home, '/login');
    }

    public function testIntendedUrlRedirectAfterLogin(): void
    {
        $this->createStudent('intended@example.com', 'password123');

        $this->get('/student/classes/1');

        $response = $this->post('/login', [
            'email' => 'intended@example.com',
            'password' => 'password123',
        ]);

        $this->assertRedirect($response, '/student/classes/1');
    }

    public function testTeacherRedirectsToTeachAfterLogin(): void
    {
        $this->createTeacher('t@example.com', 'password123');

        $response = $this->post('/login', [
            'email' => 't@example.com',
            'password' => 'password123',
        ]);

        $this->assertRedirect($response, '/teach');
    }
}
