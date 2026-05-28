<?php

declare(strict_types=1);

namespace Tests\Unit;

use Framework\Request;
use Framework\Testing\TestCase;

final class RequestTest extends TestCase
{
    public function testFromNormalizesPath(): void
    {
        $request = Request::from('GET', '/items/');

        $this->assertEquals('/items', $request->path());
    }

    public function testInputReadsPostBody(): void
    {
        $request = Request::from('POST', '/items', [], [
            'title' => 'Book',
            'description' => 'Pages',
        ]);

        $this->assertEquals('Book', $request->input('title'));
        $this->assertEquals('Pages', $request->input('description'));
    }
}
