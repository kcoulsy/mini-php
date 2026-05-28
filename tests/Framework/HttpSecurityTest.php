<?php

declare(strict_types=1);

namespace Tests\Framework;

use Framework\HttpSecurity;
use Framework\Response;
use Framework\Testing\TestCase;

final class HttpSecurityTest extends TestCase
{
    public function testHeadersIncludeDefaultsAndCsp(): void
    {
        $headers = HttpSecurity::headers();

        $this->assertEquals('SAMEORIGIN', $headers['X-Frame-Options']);
        $this->assertEquals('nosniff', $headers['X-Content-Type-Options']);
        $this->assertContains("default-src 'self'", $headers['Content-Security-Policy']);
    }

    public function testHeadersCanBeDisabled(): void
    {
        $this->assertEquals([], HttpSecurity::headers(['headers' => false]));
    }

    public function testHstsHeaderWhenEnabled(): void
    {
        $headers = HttpSecurity::headers(['hsts' => true]);

        $this->assertContains('max-age=31536000', $headers['Strict-Transport-Security']);
    }

    public function testApplyMergesHeadersOntoResponse(): void
    {
        $response = HttpSecurity::apply(Response::html('body'), []);

        $this->assertEquals('body', $response->body());
        $this->assertEquals('SAMEORIGIN', $response->header('X-Frame-Options'));
    }

    public function testSafeRedirectPaths(): void
    {
        $this->assertTrue(Response::isSafeRedirect('/items'));
        $this->assertTrue(Response::isSafeRedirect('/items/1/edit'));
    }

    public function testUnsafeRedirectsFallBackToRoot(): void
    {
        $this->assertFalse(Response::isSafeRedirect('https://evil.test'));
        $this->assertFalse(Response::isSafeRedirect('//evil.test/phish'));

        $response = Response::redirect('//evil.test/phish');

        $this->assertEquals('/', $response->header('Location'));
    }
}
