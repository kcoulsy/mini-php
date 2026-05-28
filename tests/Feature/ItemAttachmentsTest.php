<?php

declare(strict_types=1);

namespace Tests\Feature;

use Framework\UploadedFile;
use Tests\Support\ApplicationTestCase;

final class ItemAttachmentsTest extends ApplicationTestCase
{
  protected function setUp(): void
  {
    parent::setUp();
    $this->cleanUploadDirectory();
  }

  public function testStoreWithAttachmentsAndDownload(): void
  {
    $this->actingAs($this->createUser());

    $fileOne = $this->fakeJpeg('one.jpg');
    $fileTwo = $this->fakeJpeg('two.jpg');

    $response = $this->postMultipart('/items', [
      'title' => 'With files',
      'description' => 'Has attachments',
    ], [
      'attachments' => [$fileOne, $fileTwo],
    ]);

    $this->assertRedirect($response, '/items');

    $show = $this->get('/items/1');
    $this->assertSee($show, 'one.jpg');
    $this->assertSee($show, 'two.jpg');

    $download = $this->get('/items/1/attachments/1');
    $this->assertEquals(200, $download->status());
    $this->assertEquals('image/jpeg', $download->header('Content-Type'));
    $this->assertFalse($download->body() === '');
  }

  public function testUserCannotDownloadAnotherUsersAttachment(): void
  {
    $ownerId = $this->createUser('owner@example.com');
    $this->actingAs($ownerId);
    $this->postMultipart('/items', [
      'title' => 'Private',
      'description' => '',
    ], [
      'attachments' => [$this->fakeJpeg()],
    ]);

    $this->actingAs($this->createUser('other@example.com'));

    $response = $this->get('/items/1/attachments/1');

    $this->assertEquals(404, $response->status());
  }

  public function testUpdateRemovesAndAddsAttachments(): void
  {
    $this->actingAs($this->createUser());

    $this->postMultipart('/items', [
      'title' => 'Mutable',
      'description' => '',
    ], [
      'attachments' => [$this->fakeJpeg('keep.jpg'), $this->fakeJpeg('remove.jpg')],
    ]);

    $response = $this->postMultipart('/items/1', [
      'title' => 'Mutable',
      'description' => 'Updated',
      'removed_attachment_ids' => ['2'],
    ], [
      'attachments' => [$this->fakeJpeg('added.jpg')],
    ]);

    $this->assertRedirect($response, '/items/1');

    $show = $this->get('/items/1');
    $this->assertSee($show, 'keep.jpg');
    $this->assertSee($show, 'added.jpg');
    $this->assertNotSee($show, 'remove.jpg');
  }

  public function testDestroyDeletesFilesFromDisk(): void
  {
    $this->actingAs($this->createUser());

    $this->postMultipart('/items', [
      'title' => 'Temporary',
      'description' => '',
    ], [
      'attachments' => [$this->fakeJpeg()],
    ]);

    /** @var array<string, mixed> $config */
    $config = require dirname(__DIR__, 2) . '/config/testing.php';
    $uploadRoot = (string) $config['uploads']['path'];

    $this->assertTrue(count(glob($uploadRoot . '/*/*/*') ?: []) > 0);

    $this->post('/items/1/delete');

    $this->assertTrue(count(glob($uploadRoot . '/*/*/*') ?: []) === 0);
  }

  private function cleanUploadDirectory(): void
  {
    /** @var array<string, mixed> $config */
    $config = require dirname(__DIR__, 2) . '/config/testing.php';
    $root = (string) $config['uploads']['path'];

    if (!is_dir($root)) {
      return;
    }

    $this->removeDirectory($root);
    mkdir($root, 0755, true);
  }

  private function removeDirectory(string $directory): void
  {
    if (!is_dir($directory)) {
      return;
    }

    foreach (scandir($directory) ?: [] as $entry) {
      if ($entry === '.' || $entry === '..') {
        continue;
      }

      $path = $directory . DIRECTORY_SEPARATOR . $entry;

      if (is_dir($path)) {
        $this->removeDirectory($path);
      } else {
        unlink($path);
      }
    }

    rmdir($directory);
  }

  private function fakeJpeg(string $name = 'photo.jpg'): UploadedFile
  {
    $path = tempnam(sys_get_temp_dir(), 'miniphp-jpeg-');
    $jpeg = base64_decode(
      '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////2wBDAf//////////////////////////////////////wAARCAABAAEDAREAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=',
      true,
    );
    file_put_contents($path, $jpeg);

    return UploadedFile::fake($path, $name, 'image/jpeg', filesize($path));
  }
}
