<?php

declare(strict_types=1);

namespace Tests\App\Unit;

use App\Models\User;
use Tests\Support\DatabaseTestCase;

final class UserTest extends DatabaseTestCase
{
    public function testCreateStoresHashedPassword(): void
    {
        $id = User::create('User@Example.COM', 'password123', 'Pat');

        $user = User::find($id);

        $this->assertNotNull($user);
        $this->assertEquals('user@example.com', $user['email']);
        $this->assertEquals('Pat', $user['name']);
        $this->assertTrue((string) $user['password'] !== 'password123');
        $this->assertTrue(password_verify('password123', (string) $user['password']));
    }

    public function testFindByEmailIsCaseInsensitive(): void
    {
        User::create('mixed@Example.com', 'password123');

        $user = User::findByEmail('MIXED@example.com');

        $this->assertNotNull($user);
        $this->assertEquals('mixed@example.com', $user['email']);
    }
}
