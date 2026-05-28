<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Item;
use Tests\Support\DatabaseTestCase;

final class ItemTest extends DatabaseTestCase
{
    public function testCreateReturnsIdAndFindRetrievesRow(): void
    {
        $id = Item::create('Widget', 'A useful widget');

        $this->assertEquals(1, $id);

        $item = Item::find($id);

        $this->assertNotNull($item);
        $this->assertEquals('Widget', $item['title']);
        $this->assertEquals('A useful widget', $item['description']);
        $this->assertEquals($id, (int) $item['id']);
    }

    public function testFindReturnsNullForMissingId(): void
    {
        $this->assertNull(Item::find(999));
    }

    public function testAllReturnsNewestFirst(): void
    {
        Item::create('First', '');
        Item::create('Second', '');

        $items = Item::all();

        $this->assertCount(2, $items);
        $this->assertEquals('Second', $items[0]['title']);
        $this->assertEquals('First', $items[1]['title']);
    }

    public function testUpdateChangesFields(): void
    {
        $id = Item::create('Old title', 'Old body');

        $this->assertTrue(Item::update($id, 'New title', 'New body'));

        $item = Item::find($id);

        $this->assertNotNull($item);
        $this->assertEquals('New title', $item['title']);
        $this->assertEquals('New body', $item['description']);
    }

    public function testDeleteRemovesRow(): void
    {
        $id = Item::create('Temporary', '');

        $this->assertTrue(Item::delete($id));
        $this->assertNull(Item::find($id));
        $this->assertEquals([], Item::all());
    }
}
