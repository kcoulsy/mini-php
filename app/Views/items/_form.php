<?php

use Framework\View;

/**
 * @var array<string, list<string>> $errors
 * @var array{title: string, description: string} $old
 * @var string $formAction
 * @var string $cancelHref
 * @var int|null $itemId
 * @var list<array{
 *     id: int|string,
 *     item_id: int|string,
 *     stored_path: string,
 *     original_name: string,
 *     mime_type: string,
 *     size_bytes: int|string,
 *     created_at: string
 * }> $attachments
 */

$itemId = $itemId ?? null;
$attachments = $attachments ?? [];

$existingFiles = [];

foreach ($attachments as $attachment) {
  $aid = (int) $attachment['id'];
  $existingFiles[] = [
    'source' => '/items/' . (int) $itemId . '/attachments/' . $aid,
    'options' => [
      'type' => 'local',
      'metadata' => ['attachmentId' => $aid],
      'file' => [
        'name' => (string) $attachment['original_name'],
        'size' => (int) $attachment['size_bytes'],
        'type' => (string) $attachment['mime_type'],
      ],
    ],
  ];
}

$existingJson = json_encode($existingFiles, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>

<link rel="stylesheet" href="/assets/filepond/filepond.min.css">

<form method="post" action="<?= $formAction ?>" class="card form-card" enctype="multipart/form-data">
    <?= View::csrfField() ?>
    <?php require dirname(__DIR__) . '/_form_errors.php'; ?>

    <label class="<?= View::hasFieldErrors($errors, 'title') ? 'label-invalid' : '' ?>">
        Title
        <input type="text" name="title" value="<?= $old['title'] ?>" maxlength="120" required>
        <?php View::fieldErrors($errors, 'title'); ?>
    </label>

    <label class="<?= View::hasFieldErrors($errors, 'description') ? 'label-invalid' : '' ?>">
        Description
        <textarea name="description" rows="6" maxlength="2000"><?= $old['description'] ?></textarea>
        <?php View::fieldErrors($errors, 'description'); ?>
    </label>

    <label class="<?= View::hasFieldErrors($errors, 'attachments') ? 'label-invalid' : '' ?>">
        Attachments
        <input
            type="file"
            id="item-attachments"
            name="attachments[]"
            multiple
            data-existing-files="<?= View::e($existingJson) ?>"
        >
        <?php View::fieldErrors($errors, 'attachments'); ?>
    </label>

    <div id="removed-attachment-ids"></div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="<?= $cancelHref ?>" class="btn btn-ghost">Cancel</a>
    </div>
</form>

<?php require __DIR__ . '/_filepond_script.php'; ?>
