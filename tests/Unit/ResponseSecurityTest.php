<?php

declare(strict_types=1);

namespace Tests\Unit;

use Framework\Response;
use Framework\Testing\TestCase;

final class ResponseSecurityTest extends TestCase
{
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
