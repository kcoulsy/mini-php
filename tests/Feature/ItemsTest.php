<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\Support\ApplicationTestCase;

final class ItemsTest extends ApplicationTestCase
{
    public function testIndexShowsEmptyState(): void
    {
        $response = $this->get('/items');

        $this->assertOk($response);
        $this->assertSee($response, 'No items yet');
    }

    public function testStoreCreatesItemAndRedirects(): void
    {
        $response = $this->post('/items', [
            'title' => 'First item',
            'description' => 'Details here',
        ]);

        $this->assertRedirect($response, '/items');

        $show = $this->get('/items/1');
        $this->assertSee($show, 'First item');
        $this->assertSee($show, 'Details here');
    }

    public function testStoreShowsValidationErrors(): void
    {
        $response = $this->post('/items', [
            'title' => '',
            'description' => '',
        ]);

        $this->assertOk($response);
        $this->assertSee($response, 'Title is required.');
    }

    public function testStoredHtmlInTitleIsEscapedOnShow(): void
    {
        $this->post('/items', [
            'title' => '<img src=x onerror=alert(1)>',
            'description' => '',
        ]);

        $show = $this->get('/items/1');

        $this->assertSee($show, '&lt;img src=x onerror=alert(1)&gt;');
        $this->assertNotSee($show, '<img src=x onerror=alert(1)>');
    }
}
