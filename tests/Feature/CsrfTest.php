<?php

declare(strict_types=1);

namespace Tests\Feature;

use Framework\Csrf;
use Tests\Support\ApplicationTestCase;

final class CsrfTest extends ApplicationTestCase
{
    public function testPostWithoutTokenIsRejected(): void
    {
        $response = $this->post('/items', [
            'title' => 'Blocked',
            'description' => '',
        ], withCsrf: false);

        $this->assertEquals(403, $response->status());
        $this->assertSee($response, 'Invalid or missing CSRF token');
    }

    public function testCreateFormIncludesCsrfField(): void
    {
        $response = $this->get('/items/create');

        $this->assertOk($response);
        $this->assertSee($response, 'name="' . Csrf::FIELD . '"');
        $this->assertSee($response, 'value="' . Csrf::token() . '"');
    }

    public function testSecurityHeadersArePresent(): void
    {
        $response = $this->get('/items');

        $this->assertEquals('SAMEORIGIN', $response->header('X-Frame-Options'));
        $this->assertEquals('nosniff', $response->header('X-Content-Type-Options'));
        $this->assertNotNull($response->header('Content-Security-Policy'));
    }
}
