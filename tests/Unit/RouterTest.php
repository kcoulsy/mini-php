<?php

declare(strict_types=1);

namespace Tests\Unit;

use Framework\Request;
use Framework\Response;
use Framework\Router;
use Framework\Testing\TestCase;

final class RouterTest extends TestCase
{
    public function testMatchesStaticRoute(): void
    {
        $router = new Router();
        $router->get('/hello', fn () => Response::html('hi'));

        $response = $router->dispatch(Request::from('GET', '/hello'));

        $this->assertEquals(200, $response->status());
        $this->assertEquals('hi', $response->body());
    }

    public function testMatchesRouteWithParameter(): void
    {
        $router = new Router();
        $router->get('/items/{id}', fn (Request $request, string $id) => Response::html("id={$id}"));

        $response = $router->dispatch(Request::from('GET', '/items/42'));

        $this->assertContains('id=42', $response->body());
    }

    public function testReturns404WhenNoRouteMatches(): void
    {
        $router = new Router();
        $response = $router->dispatch(Request::from('GET', '/missing'));

        $this->assertEquals(404, $response->status());
    }
}
