<?php

declare(strict_types=1);

namespace Framework\Testing\Concerns;

use Framework\Request;
use Framework\Response;

trait InteractsWithHttp
{
    abstract protected function dispatch(Request $request): Response;

    protected function get(string $path, array $query = []): Response
    {
        return $this->dispatch(Request::from('GET', $path, $query));
    }

    /** @param array<string, mixed> $body */
    protected function post(string $path, array $body = []): Response
    {
        return $this->dispatch(Request::from('POST', $path, [], $body));
    }

    protected function assertOk(Response $response, string $message = ''): void
    {
        $this->assertEquals(200, $response->status(), $message !== '' ? $message : 'Expected HTTP 200.');
    }

    protected function assertRedirect(Response $response, string $location, string $message = ''): void
    {
        $this->assertTrue(
            in_array($response->status(), [301, 302, 303, 307, 308], true),
            $message !== '' ? $message : 'Expected redirect response.',
        );
        $this->assertEquals($location, $response->header('Location'), $message);
    }

    protected function assertSee(Response $response, string $text, string $message = ''): void
    {
        $this->assertContains($text, $response->body(), $message);
    }

    protected function assertNotSee(Response $response, string $text, string $message = ''): void
    {
        $this->assertFalse(str_contains($response->body(), $text), $message !== '' ? $message : "Did not expect [{$text}].");
    }
}
