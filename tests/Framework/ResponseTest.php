<?php

declare(strict_types=1);

namespace Tests\Framework;

use Framework\Response;
use Framework\Testing\TestCase;

final class ResponseTest extends TestCase
{
    public function testHtmlSetsBodyAndStatus(): void
    {
        $response = Response::html('<p>Hi</p>', 201);

        $this->assertEquals(201, $response->status());
        $this->assertEquals('<p>Hi</p>', $response->body());
        $this->assertEquals('text/html; charset=UTF-8', $response->header('Content-Type'));
    }

    public function testRedirectSetsLocationHeader(): void
    {
        $response = Response::redirect('/dashboard', 303);

        $this->assertEquals(303, $response->status());
        $this->assertEquals('/dashboard', $response->header('Location'));
        $this->assertEquals('', $response->body());
    }

    public function testDownloadReturnsFileBody(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'miniphp-dl-');
        file_put_contents($path, 'file-bytes');

        $response = Response::download($path, 'report.pdf', 'application/pdf');

        $this->assertEquals(200, $response->status());
        $this->assertEquals('file-bytes', $response->body());
        $this->assertEquals('application/pdf', $response->header('Content-Type'));
        $this->assertContains('report.pdf', (string) $response->header('Content-Disposition'));

        unlink($path);
    }

    public function testWithHeadersMergesWithoutReplacingBody(): void
    {
        $response = Response::html('x')->withHeaders(['X-Test' => '1']);

        $this->assertEquals('x', $response->body());
        $this->assertEquals('1', $response->header('X-Test'));
        $this->assertEquals('text/html; charset=UTF-8', $response->header('Content-Type'));
    }

    public function testHeaderReturnsDefaultWhenMissing(): void
    {
        $response = Response::html('');

        $this->assertNull($response->header('Missing'));
        $this->assertEquals('fallback', $response->header('Missing', 'fallback'));
    }

    public function testEmptyPathIsNotSafeRedirect(): void
    {
        $this->assertFalse(Response::isSafeRedirect(''));
    }
}
