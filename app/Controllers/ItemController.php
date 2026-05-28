<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Item;
use Framework\Controller;
use Framework\Request;
use Framework\Response;

final class ItemController extends Controller
{
  public function index(Request $request): Response
  {
    return $this->render('items/index', [
      'items' => Item::all(),
      'flash' => $this->flash(),
    ]);
  }

  public function show(Request $request, string $id): Response
  {
    $item = Item::find((int) $id);

    if ($item === null) {
      return Response::html('Item not found.', 404);
    }

    return $this->render('items/show', ['item' => $item]);
  }

  public function create(Request $request): Response
  {
    return $this->render('items/create', $this->createFormData());
  }

  public function store(Request $request): Response
  {
    [$title, $description, $errors] = $this->validate($request);

    if ($errors !== []) {
      return $this->render('items/create', $this->createFormData($errors, $title, $description));
    }

    Item::create($title, $description);
    $this->setFlash('Item created.');

    return $this->redirect('/items');
  }

  public function edit(Request $request, string $id): Response
  {
    $item = Item::find((int) $id);

    if ($item === null) {
      return Response::html('Item not found.', 404);
    }

    return $this->render('items/edit', $this->editFormData($item));
  }

  public function update(Request $request, string $id): Response
  {
    $item = Item::find((int) $id);

    if ($item === null) {
      return Response::html('Item not found.', 404);
    }

    [$title, $description, $errors] = $this->validate($request);

    if ($errors !== []) {
      return $this->render('items/edit', $this->editFormData($item, $errors, $title, $description));
    }

    Item::update((int) $id, $title, $description);
    $this->setFlash('Item updated.');

    return $this->redirect('/items/' . $id);
  }

  public function destroy(Request $request, string $id): Response
  {
    Item::delete((int) $id);
    $this->setFlash('Item deleted.');

    return $this->redirect('/items');
  }

  /**
   * @param list<string> $errors
   * @return array<string, mixed>
   */
  private function createFormData(
    array $errors = [],
    string $title = '',
    string $description = '',
  ): array {
    return [
      'title' => 'New item',
      'errors' => $errors,
      'old' => ['title' => $title, 'description' => $description],
      'formAction' => '/items',
      'cancelHref' => '/items',
    ];
  }

  /**
   * @param array<string, mixed> $item
   * @param list<string> $errors
   * @return array<string, mixed>
   */
  private function editFormData(
    array $item,
    array $errors = [],
    ?string $title = null,
    ?string $description = null,
  ): array {
    $id = (int) $item['id'];

    return [
      'title' => 'Edit item',
      'errors' => $errors,
      'old' => [
        'title' => $title ?? (string) $item['title'],
        'description' => $description ?? (string) $item['description'],
      ],
      'formAction' => '/items/' . $id,
      'cancelHref' => '/items/' . $id,
    ];
  }

  /** @return array{0: string, 1: string, 2: list<string>} */
  private function validate(Request $request): array
  {
    $title = trim((string) $request->input('title', ''));
    $description = trim((string) $request->input('description', ''));
    $errors = [];

    if ($title === '') {
      $errors[] = 'Title is required.';
    } elseif (mb_strlen($title) > 120) {
      $errors[] = 'Title must be 120 characters or fewer.';
    }

    if (mb_strlen($description) > 2000) {
      $errors[] = 'Description must be 2000 characters or fewer.';
    }

    return [$title, $description, $errors];
  }

  private function setFlash(string $message): void
  {
    $_SESSION['flash'] = $message;
  }

  private function flash(): ?string
  {
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return is_string($message) ? $message : null;
  }
}
