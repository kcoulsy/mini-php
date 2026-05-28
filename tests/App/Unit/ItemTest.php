<?php

declare(strict_types=1);

namespace Tests\App\Unit;

use App\Models\Item;
use App\Models\User;
use Tests\Support\DatabaseTestCase;

final class ItemTest extends DatabaseTestCase
{
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userId = User::create('test@example.com', 'password123', 'Test');
    }

    public function testCreateReturnsIdAndFindRetrievesRow(): void
    {
        $id = Item::create('Widget', 'A useful widget', $this->userId);

        $this->assertEquals(1, $id);

        $item = Item::findForUser($id, $this->userId);

        $this->assertNotNull($item);
        $this->assertEquals('Widget', $item['title']);
        $this->assertEquals('A useful widget', $item['description']);
        $this->assertEquals($id, (int) $item['id']);
        $this->assertEquals($this->userId, (int) $item['user_id']);
    }

    public function testFindReturnsNullForMissingId(): void
    {
        $this->assertNull(Item::findForUser(999, $this->userId));
    }

    public function testFindReturnsNullForAnotherUsersItem(): void
    {
        $id = Item::create('Owned', '', $this->userId);
        $otherId = User::create('other@example.com', 'password123');

        $this->assertNull(Item::findForUser($id, $otherId));
    }

    public function testAllForUserReturnsNewestFirst(): void
    {
        Item::create('First', '', $this->userId);
        Item::create('Second', '', $this->userId);

        $items = Item::allForUser($this->userId);

        $this->assertCount(2, $items);
        $this->assertEquals('Second', $items[0]['title']);
        $this->assertEquals('First', $items[1]['title']);
    }

    public function testUpdateChangesFields(): void
    {
        $id = Item::create('Old title', 'Old body', $this->userId);

        $this->assertTrue(Item::update($id, $this->userId, 'New title', 'New body'));

        $item = Item::findForUser($id, $this->userId);

        $this->assertNotNull($item);
        $this->assertEquals('New title', $item['title']);
        $this->assertEquals('New body', $item['description']);
    }

    public function testDeleteRemovesRow(): void
    {
        $id = Item::create('Temporary', '', $this->userId);

        $this->assertTrue(Item::delete($id, $this->userId));
        $this->assertNull(Item::findForUser($id, $this->userId));
        $this->assertEquals([], Item::allForUser($this->userId));
    }
}
