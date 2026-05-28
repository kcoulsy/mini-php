<?php

declare(strict_types=1);

use App\Controllers\ItemController;
use Framework\App;
use Framework\Response;

/** @var App $app */
$router = $app->router();
$view = $app->view();

$items = new ItemController($view);

$router->get('/', fn () => Response::redirect('/items'));

$router->get('/items', fn ($request) => $items->index($request));
$router->get('/items/create', fn ($request) => $items->create($request));
$router->post('/items', fn ($request) => $items->store($request));
$router->get('/items/{id}', fn ($request, $id) => $items->show($request, $id));
$router->get('/items/{id}/edit', fn ($request, $id) => $items->edit($request, $id));
$router->post('/items/{id}', fn ($request, $id) => $items->update($request, $id));
$router->post('/items/{id}/delete', fn ($request, $id) => $items->destroy($request, $id));
