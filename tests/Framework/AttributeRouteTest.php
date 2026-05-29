<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Http\HomeRedirect;
use Framework\Request;
use Framework\Response;
use Framework\Router;
use Framework\Routing\ActionInvoker;
use Framework\Routing\HandlerContainer;
use Framework\Testing\TestCase;
use Framework\View;

final class AttributeRouteTest extends TestCase
{
    public function testRegistersInvokableRoute(): void
    {
        $router = new Router();
        $container = new HandlerContainer(new View(sys_get_temp_dir()));
        $registrar = new \Framework\Routing\RouteRegistrar($router, new ActionInvoker());
        $registrar->registerClass(HomeRedirect::class, $container);

        $response = $router->dispatch(Request::from('GET', '/'));

        $this->assertEquals(302, $response->status());
        $this->assertEquals('/login', $response->header('Location'));
    }
}
