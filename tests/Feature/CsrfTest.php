<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\Support\ApplicationTestCase;

final class CsrfTest extends ApplicationTestCase
{
    public function testPostWithoutCsrfTokenReturns403(): void
    {
        $this->actingAs($this->createStudent());

        $response = $this->post('/student/classes/join', [
            'join_code' => 'ABC12345',
        ], withCsrf: false);

        $this->assertEquals(403, $response->status());
        $this->assertSee($response, 'Invalid or missing CSRF token');
    }

    public function testGetDoesNotRequireCsrf(): void
    {
        $this->actingAs($this->createStudent());

        $response = $this->get('/student');

        $this->assertOk($response);
    }
}
