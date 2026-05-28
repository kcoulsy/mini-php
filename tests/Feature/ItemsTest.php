<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\Support\ApplicationTestCase;

final class ItemsTest extends ApplicationTestCase
{
    public function testIndexShowsEmptyState(): void
    {
        $this->actingAs($this->createUser());

        $response = $this->get('/items');

        $this->assertOk($response);
        $this->assertSee($response, 'No items yet');
    }

    public function testStoreCreatesItemAndRedirects(): void
    {
        $this->actingAs($this->createUser());

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
        $this->actingAs($this->createUser());

        $response = $this->post('/items', [
            'title' => '',
            'description' => '',
        ]);

        $this->assertOk($response);
        $this->assertSee($response, 'Title is required.');
    }

    public function testStoredHtmlInTitleIsEscapedOnShow(): void
    {
        $this->actingAs($this->createUser());

        $this->post('/items', [
            'title' => '<img src=x onerror=alert(1)>',
            'description' => '',
        ]);

        $show = $this->get('/items/1');

        $this->assertSee($show, '&lt;img src=x onerror=alert(1)&gt;');
        $this->assertNotSee($show, '<img src=x onerror=alert(1)>');
    }

    public function testUserCannotViewAnotherUsersItem(): void
    {
        $ownerId = $this->createUser('owner@example.com');
        $this->actingAs($ownerId);
        $this->post('/items', ['title' => 'Private item', 'description' => '']);

        $otherId = $this->createUser('other@example.com');
        $this->actingAs($otherId);

        $response = $this->get('/items/1');

        $this->assertEquals(404, $response->status());
        $this->assertSee($response, 'Item not found.');
    }
}
