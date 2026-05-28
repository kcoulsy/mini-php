<?php

declare(strict_types=1);

namespace Tests\Unit;

use Framework\Csrf;
use Framework\Request;
use Framework\Session;
use Framework\Testing\TestCase;

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        Session::start();
        $_SESSION = [];
    }

    public function testTokenIsStableWithinSession(): void
    {
        $first = Csrf::token();
        $second = Csrf::token();

        $this->assertEquals($first, $second);
        $this->assertEquals(64, strlen($first));
    }

    public function testRegenerateChangesToken(): void
    {
        $original = Csrf::token();
        $rotated = Csrf::regenerate();

        $this->assertFalse($original === $rotated);
        $this->assertEquals($rotated, Csrf::token());
    }

    public function testValidateAcceptsMatchingToken(): void
    {
        $token = Csrf::token();
        $request = Request::from('POST', '/items', [], [Csrf::FIELD => $token]);

        $this->assertTrue(Csrf::validate($request));
    }

    public function testValidateRejectsMissingOrWrongToken(): void
    {
        Csrf::token();

        $this->assertFalse(Csrf::validate(Request::from('POST', '/items')));
        $this->assertFalse(Csrf::validate(Request::from('POST', '/items', [], [
            Csrf::FIELD => 'invalid',
        ])));
    }
}
