<?php

declare(strict_types=1);

namespace Tests\Framework;

use Framework\FileStorage;
use Framework\UploadedFile;
use Framework\Testing\TestCase;

final class FileStorageTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/miniphp-storage-' . bin2hex(random_bytes(4));
        mkdir($this->basePath, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);
    }

    public function testStoreAndDeleteFile(): void
    {
        $storage = new FileStorage($this->basePath);
        $source = $this->createTempJpeg();
        $file = UploadedFile::fake($source, 'photo.jpg', 'image/jpeg', filesize($source));

        $relative = $storage->store($file, '1/2');

        $this->assertTrue($storage->exists($relative));
        $storage->delete($relative);
        $this->assertFalse($storage->exists($relative));
    }

    private function createTempJpeg(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'miniphp-jpeg-');
        $jpeg = base64_decode(
            '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////2wBDAf//////////////////////////////////////wAARCAABAAEDAREAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=',
            true,
        );
        file_put_contents($path, $jpeg);

        return $path;
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
}
