<?php

declare(strict_types=1);

namespace Tests\Framework;

use Framework\Middleware\Authenticate;
use Framework\Request;
use Framework\Response;
use Framework\Router;
use Framework\Session;
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

    public function testPostRouteMatchesPostRequests(): void
    {
        $router = new Router();
        $router->post('/items', fn () => Response::html('created'));

        $response = $router->dispatch(Request::from('POST', '/items'));

        $this->assertEquals('created', $response->body());
    }

    public function testMiddlewareCanShortCircuit(): void
    {
        Session::start();
        $_SESSION = [];

        $router = new Router();
        $router->get('/secret', fn () => Response::html('never'), [Authenticate::class]);

        $response = $router->dispatch(Request::from('GET', '/secret'));

        $this->assertEquals(302, $response->status());
        $this->assertEquals('/login', $response->header('Location'));
    }

    public function testHandlerMayReturnString(): void
    {
        $router = new Router();
        $router->get('/text', fn () => 'plain');

        $response = $router->dispatch(Request::from('GET', '/text'));

        $this->assertEquals('plain', $response->body());
    }

    public function testInvalidHandlerReturnThrows(): void
    {
        $router = new Router();
        $router->get('/bad', fn () => 123);

        $threw = false;

        try {
            $router->dispatch(Request::from('GET', '/bad'));
        } catch (\RuntimeException) {
            $threw = true;
        }

        $this->assertTrue($threw);
    }
}
