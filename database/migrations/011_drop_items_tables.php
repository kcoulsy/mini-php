<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec('DROP TABLE IF EXISTS item_attachments');
    $pdo->exec('DROP TABLE IF EXISTS items');
};
