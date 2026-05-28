<?php

declare(strict_types=1);

namespace Tests\Framework;

use Framework\UploadedFile;
use Framework\UploadValidator;
use Framework\Testing\TestCase;

final class UploadedFileTest extends TestCase
{
    public function testFakeFileIsValidAndDetectsMime(): void
    {
        $path = $this->createTempJpeg();
        $file = UploadedFile::fake($path, 'photo.jpg', 'image/jpeg', filesize($path));

        $this->assertTrue($file->isValid());
        $this->assertEquals('image/jpeg', $file->detectedMimeType());
        $this->assertEquals('jpg', $file->extension());
    }

    public function testValidatorRejectsDisallowedMime(): void
    {
        $path = $this->createTempTextFile();
        $file = UploadedFile::fake($path, 'notes.txt', 'text/plain', filesize($path));

        $errors = UploadValidator::validateMany([
            'max_bytes' => 1_048_576,
            'max_files_per_request' => 5,
            'allowed_mimes' => ['image/jpeg'],
        ], [$file]);

        $this->assertTrue($errors !== []);
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

    private function createTempTextFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'miniphp-txt-');
        file_put_contents($path, 'hello');

        return $path;
    }
}
