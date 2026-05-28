<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Item;
use App\Models\ItemAttachment;
use Framework\Auth;
use Framework\Controller;
use Framework\FileStorage;
use Framework\Request;
use Framework\Response;

/**
 * @phpstan-import-type ItemRow from App\Models\Item
 */
final class ItemController extends Controller
{
  private FileStorage $storage;

  /** @param array<string, mixed> $uploadConfig */
  public function __construct(
    \Framework\View $view,
    private readonly array $uploadConfig = [],
  ) {
    parent::__construct($view);
    $this->storage = FileStorage::fromConfig($uploadConfig);
  }

  public function index(Request $request): Response
  {
    $userId = $this->userId();
    $items = Item::allForUser($userId);

    foreach ($items as &$item) {
      $item['attachment_count'] = count(ItemAttachment::forItem((int) $item['id']));
    }
    unset($item);

    return $this->render('items/index', [
      'items' => $items,
      'flash' => $this->flash(),
    ]);
  }

  public function show(Request $request, string $id): Response
  {
    $item = Item::findForUser((int) $id, $this->userId());

    if ($item === null) {
      return Response::html('Item not found.', 404);
    }

    return $this->render('items/show', [
      'item' => $item,
      'attachments' => ItemAttachment::forItem((int) $item['id']),
    ]);
  }

  public function create(Request $request): Response
  {
    return $this->render('items/create', $this->createFormData());
  }

  public function store(Request $request): Response
  {
    [$title, $description, $errors] = $this->validate($request);
    $uploadErrors = $this->validateUploads($request);

    if ($errors !== [] || $uploadErrors !== []) {
      return $this->render('items/create', $this->createFormData(
        array_merge($errors, $uploadErrors),
        $title,
        $description,
      ));
    }

    $itemId = Item::create($title, $description, $this->userId());
    $fileErrors = ItemAttachment::createMany(
      $itemId,
      $request->files('attachments'),
      $this->userId(),
      $this->uploadConfig,
      $this->storage,
    );

    if ($fileErrors !== []) {
      Item::delete($itemId, $this->userId(), $this->storage);

      return $this->render('items/create', $this->createFormData($fileErrors, $title, $description));
    }

    $this->setFlash('Item created.');

    return $this->redirect('/items');
  }

  public function edit(Request $request, string $id): Response
  {
    $item = Item::findForUser((int) $id, $this->userId());

    if ($item === null) {
      return Response::html('Item not found.', 404);
    }

    return $this->render('items/edit', $this->editFormData($item));
  }

  public function update(Request $request, string $id): Response
  {
    $itemId = (int) $id;
    $userId = $this->userId();
    $item = Item::findForUser($itemId, $userId);

    if ($item === null) {
      return Response::html('Item not found.', 404);
    }

    [$title, $description, $errors] = $this->validate($request);
    $uploadErrors = $this->validateUploads($request);

    if ($errors !== [] || $uploadErrors !== []) {
      return $this->render('items/edit', $this->editFormData($item, array_merge($errors, $uploadErrors), $title, $description));
    }

    Item::update($itemId, $userId, $title, $description);
    ItemAttachment::deleteIds($itemId, $userId, $this->removedAttachmentIds($request), $this->storage);

    $fileErrors = ItemAttachment::createMany(
      $itemId,
      $request->files('attachments'),
      $userId,
      $this->uploadConfig,
      $this->storage,
    );

    if ($fileErrors !== []) {
      return $this->render('items/edit', $this->editFormData($item, $fileErrors, $title, $description));
    }

    $this->setFlash('Item updated.');

    return $this->redirect('/items/' . $itemId);
  }

  public function destroy(Request $request, string $id): Response
  {
    $itemId = (int) $id;
    $userId = $this->userId();

    if (Item::findForUser($itemId, $userId) === null) {
      return Response::html('Item not found.', 404);
    }

    Item::delete($itemId, $userId, $this->storage);
    $this->setFlash('Item deleted.');

    return $this->redirect('/items');
  }

  public function downloadAttachment(Request $request, string $id, string $attachmentId): Response
  {
    $itemId = (int) $id;
    $attachment = ItemAttachment::findForUser((int) $attachmentId, $itemId, $this->userId());

    if ($attachment === null) {
      return Response::html('Item not found.', 404);
    }

    $absolute = $this->storage->absolutePath((string) $attachment['stored_path']);

    return Response::download(
      $absolute,
      (string) $attachment['original_name'],
      (string) $attachment['mime_type'],
    );
  }

  private function userId(): int
  {
    $id = Auth::id();

    if ($id === null) {
      throw new \RuntimeException('Authenticated user required.');
    }

    return $id;
  }

  /** @return list<string> */
  private function validateUploads(Request $request): array
  {
    $files = $request->files('attachments');

    if ($files === []) {
      return [];
    }

    return \Framework\UploadValidator::validateMany($this->uploadConfig, $files);
  }

  /** @return list<int> */
  private function removedAttachmentIds(Request $request): array
  {
    $raw = $request->input('removed_attachment_ids', []);

    if (!is_array($raw)) {
      return [];
    }

    $ids = [];

    foreach ($raw as $value) {
      if (is_numeric($value)) {
        $ids[] = (int) $value;
      }
    }

    return $ids;
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
      'itemId' => null,
      'attachments' => [],
    ];
  }

  /**
   * @param ItemRow $item
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
      'itemId' => $id,
      'attachments' => ItemAttachment::forItem($id),
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
