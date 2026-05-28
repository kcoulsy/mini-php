<?php

declare(strict_types=1);

namespace Framework;

final class UploadValidator
{
  /**
   * @param array<string, mixed> $config
   * @param list<UploadedFile> $files
   * @return list<string>
   */
  public static function validateMany(array $config, array $files): array
  {
    $errors = [];
    $maxFiles = (int) ($config['max_files_per_request'] ?? 10);
    $maxBytes = (int) ($config['max_bytes'] ?? 5_242_880);
    /** @var list<string> $allowedMimes */
    $allowedMimes = $config['allowed_mimes'] ?? [];

    if (count($files) > $maxFiles) {
      $errors[] = "You may upload at most {$maxFiles} files at a time.";

      return $errors;
    }

    foreach ($files as $index => $file) {
      $label = 'File ' . ($index + 1);
      $name = $file->clientOriginalName();

      if ($name !== '') {
        $label = '"' . $name . '"';
      }

      if (!$file->isValid()) {
        $errors[] = "{$label} could not be uploaded (error code {$file->error()}).";

        continue;
      }

      if ($file->size() > $maxBytes) {
        $maxMb = round($maxBytes / 1_048_576, 1);
        $errors[] = "{$label} exceeds the maximum size of {$maxMb} MB.";

        continue;
      }

      $mime = $file->detectedMimeType();

      if ($mime === '' || !in_array($mime, $allowedMimes, true)) {
        $errors[] = "{$label} has a file type that is not allowed.";
      }
    }

    return $errors;
  }
}
