<?php

declare(strict_types=1);

namespace Tests\Framework;

use Framework\Validator;
use Framework\Testing\TestCase;

final class ValidatorTest extends TestCase
{
    public function testRequiredFailsOnEmptyString(): void
    {
        $v = Validator::make(['title' => ''], ['title' => 'required'], [
            'title.required' => 'Title is required.',
        ]);

        $this->assertTrue($v->fails());
        $this->assertEquals(['title' => ['Title is required.']], $v->errors());
    }

    public function testTrimMutatesValue(): void
    {
        $v = Validator::make(['name' => '  Ada  '], ['name' => 'trim']);

        $this->assertTrue($v->passes());
        $this->assertEquals('Ada', $v->get('name'));
    }

    public function testMaxStopsFurtherRulesOnSameField(): void
    {
        $v = Validator::make(
            ['title' => str_repeat('a', 121)],
            ['title' => 'required|max:120'],
            ['title.max' => 'Too long.'],
        );

        $this->assertTrue($v->fails());
        $this->assertEquals(['title' => ['Too long.']], $v->errors());
    }

    public function testEmailRuleRejectsInvalid(): void
    {
        $v = Validator::make(['email' => 'not-an-email'], ['email' => 'email']);

        $this->assertTrue($v->fails());
        $this->assertTrue(isset($v->errors()['email'][0]));
    }

    public function testMinRule(): void
    {
        $v = Validator::make(
            ['password' => 'short'],
            ['password' => 'min:8'],
            ['password.min' => 'Password must be at least 8 characters.'],
        );

        $this->assertTrue($v->fails());
        $this->assertEquals(
            ['password' => ['Password must be at least 8 characters.']],
            $v->errors(),
        );
    }

    public function testConfirmedRuleOnPasswordField(): void
    {
        $v = Validator::make(
            [
                'password' => 'secret123',
                'password_confirmation' => 'different',
            ],
            ['password' => 'confirmed'],
            ['password.confirmed' => 'Password confirmation does not match.'],
        );

        $this->assertTrue($v->fails());
        $this->assertEquals(
            ['password' => ['Password confirmation does not match.']],
            $v->errors(),
        );
    }

    public function testConfirmedRuleOnConfirmationField(): void
    {
        $v = Validator::make(
            [
                'password' => 'secret123',
                'password_confirmation' => 'different',
            ],
            ['password_confirmation' => 'confirmed'],
            ['password_confirmation.confirmed' => 'Password confirmation does not match.'],
        );

        $this->assertTrue($v->fails());
        $this->assertEquals(
            ['password_confirmation' => ['Password confirmation does not match.']],
            $v->errors(),
        );
    }

    public function testCustomCallableRule(): void
    {
        $v = Validator::make(
            ['email' => 'taken@example.com'],
            [
                'email' => [
                    'email',
                    static fn (mixed $value): ?string => $value === 'taken@example.com'
                        ? 'Email is already taken.'
                        : null,
                ],
            ],
        );

        $this->assertTrue($v->fails());
        $this->assertEquals(['email' => ['Email is already taken.']], $v->errors());
    }

    public function testCustomCallableSkippedAfterEarlierFailure(): void
    {
        $called = false;

        $v = Validator::make(
            ['email' => ''],
            [
                'email' => [
                    'required',
                    static function () use (&$called): ?string {
                        $called = true;

                        return 'Should not run.';
                    },
                ],
            ],
            ['email.required' => 'Email required.'],
        );

        $this->assertTrue($v->fails());
        $this->assertFalse($called);
        $this->assertEquals(['email' => ['Email required.']], $v->errors());
    }

    public function testValidatedReturnsOnlyRuledFields(): void
    {
        $v = Validator::make(
            ['title' => 'Hello', 'extra' => 'ignored'],
            ['title' => 'required'],
        );

        $this->assertEquals(['title' => 'Hello'], $v->validated());
    }

    public function testPipeAndArrayRulesAreEquivalent(): void
    {
        $pipe = Validator::make(['title' => 'Hi'], ['title' => 'trim|required|max:120']);
        $array = Validator::make(['title' => 'Hi'], ['title' => ['trim', 'required', 'max:120']]);

        $this->assertTrue($pipe->passes());
        $this->assertTrue($array->passes());
        $this->assertEquals($pipe->get('title'), $array->get('title'));
    }
}
