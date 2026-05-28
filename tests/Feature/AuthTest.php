<?php

declare(strict_types=1);

namespace Tests\Feature;

use Framework\Session;
use Tests\Support\ApplicationTestCase;

final class AuthTest extends ApplicationTestCase
{
    public function testGuestIsRedirectedFromItemsToLogin(): void
    {
        $response = $this->get('/items');

        $this->assertRedirect($response, '/login');
    }

    public function testRegisterCreatesUserAndRedirectsToItems(): void
    {
        $response = $this->post('/register', [
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $this->assertRedirect($response, '/items');

        $items = $this->get('/items');
        $this->assertOk($items);
        $this->assertSee($items, 'Ada');
    }

    public function testLoginWithValidCredentials(): void
    {
        $this->createUser('login@example.com', 'password123');

        $response = $this->post('/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $this->assertRedirect($response, '/items');
    }

    public function testLoginWithInvalidCredentialsShowsGenericError(): void
    {
        $this->createUser('login@example.com', 'password123');

        $response = $this->post('/login', [
            'email' => 'login@example.com',
            'password' => 'wrong',
        ]);

        $this->assertOk($response);
        $this->assertSee($response, 'Invalid credentials.');
    }

    public function testAuthenticatedUserIsRedirectedFromLoginToItems(): void
    {
        $userId = $this->createUser();
        $this->actingAs($userId);

        $response = $this->get('/login');

        $this->assertRedirect($response, '/items');
    }

    public function testLogoutClearsSession(): void
    {
        $userId = $this->createUser();
        $this->actingAs($userId);

        $response = $this->post('/logout');

        $this->assertRedirect($response, '/login');
        $this->assertFalse(isset($_SESSION[Session::USER_KEY]));

        $items = $this->get('/items');
        $this->assertRedirect($items, '/login');
    }

    public function testIntendedUrlRedirectAfterLogin(): void
    {
        $this->createUser('intended@example.com', 'password123');

        $this->get('/items/create');

        $response = $this->post('/login', [
            'email' => 'intended@example.com',
            'password' => 'password123',
        ]);

        $this->assertRedirect($response, '/items/create');
    }
}
