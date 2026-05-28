<?php

declare(strict_types=1);

namespace Tests\Framework;

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

    public function testQueryReadsQueryString(): void
    {
        $request = Request::from('GET', '/search', ['q' => 'php']);

        $this->assertEquals('php', $request->query('q'));
        $this->assertEquals('default', $request->query('missing', 'default'));
    }

    public function testAllReturnsBodyArray(): void
    {
        $body = ['a' => 1, 'b' => 2];
        $request = Request::from('POST', '/items', [], $body);

        $this->assertEquals($body, $request->all());
    }

    public function testMethodIsUppercased(): void
    {
        $request = Request::from('post', '/items');

        $this->assertEquals('POST', $request->method());
    }
}
