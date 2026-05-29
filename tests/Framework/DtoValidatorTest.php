<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Data\LoginData;
use Framework\Validation\DtoValidator;
use Framework\Testing\TestCase;

final class DtoValidatorTest extends TestCase
{
    public function testLoginDataRequiresEmailAndPassword(): void
    {
        $result = DtoValidator::validate(LoginData::class, []);

        $this->assertTrue($result->fails());
        $this->assertTrue(($result->errors['email'] ?? []) !== []);
        $this->assertTrue(($result->errors['password'] ?? []) !== []);
    }

    public function testLoginDataPassesWithValidInput(): void
    {
        $result = DtoValidator::validate(LoginData::class, [
            'email' => ' user@example.com ',
            'password' => 'secret',
        ]);

        $this->assertTrue($result->passes());
        $this->assertEquals('user@example.com', $result->dto?->email);
    }
}
