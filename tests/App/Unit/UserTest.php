<?php

declare(strict_types=1);

namespace Tests\App\Unit;

use App\Models\User;
use Tests\Support\DatabaseTestCase;

final class UserTest extends DatabaseTestCase
{
    public function testCreateStoresHashedPassword(): void
    {
        $user = User::create([
            'email' => mb_strtolower(trim('User@Example.COM')),
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'name' => trim('Pat'),
            'role' => User::ROLE_STUDENT,
        ]);

        $found = User::find($user->id);

        $this->assertNotNull($found);
        $this->assertEquals('user@example.com', $found->email);
        $this->assertEquals('Pat', $found->name);
        $this->assertTrue($found->password !== 'password123');
        $this->assertTrue(password_verify('password123', $found->password));
    }

    public function testFindByEmailIsCaseInsensitive(): void
    {
        User::create([
            'email' => mb_strtolower(trim('mixed@Example.com')),
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'name' => '',
            'role' => User::ROLE_STUDENT,
        ]);

        $user = User::findBy('email', mb_strtolower(trim('MIXED@example.com')));

        $this->assertNotNull($user);
        $this->assertEquals('mixed@example.com', $user->email);
    }
}
