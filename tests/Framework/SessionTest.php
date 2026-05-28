<?php

declare(strict_types=1);

namespace Tests\Framework;

use Framework\Session;
use Framework\Testing\TestCase;

final class SessionTest extends TestCase
{
    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testStartIsIdempotent(): void
    {
        Session::start(['name' => 'miniphp_test_sid']);
        $firstId = session_id();

        Session::start(['name' => 'miniphp_test_sid']);
        $secondId = session_id();

        $this->assertEquals($firstId, $secondId);
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
    }

    public function testIsHttpsDetectsServerVariables(): void
    {
        $originalHttps = $_SERVER['HTTPS'] ?? null;
        $originalPort = $_SERVER['SERVER_PORT'] ?? null;

        $_SERVER['HTTPS'] = 'on';
        unset($_SERVER['SERVER_PORT']);
        $this->assertTrue(Session::isHttps());

        unset($_SERVER['HTTPS']);
        $_SERVER['SERVER_PORT'] = 443;
        $this->assertTrue(Session::isHttps());

        unset($_SERVER['HTTPS'], $_SERVER['SERVER_PORT']);
        $this->assertFalse(Session::isHttps());

        if ($originalHttps !== null) {
            $_SERVER['HTTPS'] = $originalHttps;
        }

        if ($originalPort !== null) {
            $_SERVER['SERVER_PORT'] = $originalPort;
        }
    }
}
