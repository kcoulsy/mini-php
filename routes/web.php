<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\ItemController;
use Framework\App;
use Framework\Auth;
use Framework\Middleware\Authenticate;
use Framework\Middleware\GuestOnly;
use Framework\Response;

/** @var App $app */
$router = $app->router();
$view = $app->view();

/** @var array<string, mixed> $authConfig */
$authConfig = $app->config('auth', []);
/** @var array<string, mixed> $uploadConfig */
$uploadConfig = $app->config('uploads', []);

$auth = new AuthController($view, is_array($authConfig) ? $authConfig : []);
$items = new ItemController($view, is_array($uploadConfig) ? $uploadConfig : []);

$authMiddleware = [Authenticate::class];
$guestMiddleware = [GuestOnly::class];

$router->get('/', fn () => Auth::check()
    ? Response::redirect('/items')
    : Response::redirect('/login'));

$router->get('/login', fn ($request) => $auth->showLogin($request), $guestMiddleware);
$router->post('/login', fn ($request) => $auth->login($request), $guestMiddleware);
$router->get('/register', fn ($request) => $auth->showRegister($request), $guestMiddleware);
$router->post('/register', fn ($request) => $auth->register($request), $guestMiddleware);
$router->post('/logout', fn ($request) => $auth->logout($request), $authMiddleware);

$router->get('/items', fn ($request) => $items->index($request), $authMiddleware);
$router->get('/items/create', fn ($request) => $items->create($request), $authMiddleware);
$router->post('/items', fn ($request) => $items->store($request), $authMiddleware);
$router->get('/items/{id}', fn ($request, $id) => $items->show($request, $id), $authMiddleware);
$router->get('/items/{id}/attachments/{attachmentId}', fn ($request, $id, $attachmentId) => $items->downloadAttachment($request, $id, $attachmentId), $authMiddleware);
$router->get('/items/{id}/edit', fn ($request, $id) => $items->edit($request, $id), $authMiddleware);
$router->post('/items/{id}', fn ($request, $id) => $items->update($request, $id), $authMiddleware);
$router->post('/items/{id}/delete', fn ($request, $id) => $items->destroy($request, $id), $authMiddleware);
