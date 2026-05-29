<?php

declare(strict_types=1);

namespace App\Models;

use Framework\Database;
use Framework\Model\Attributes\AutoIncrement;
use Framework\Model\Attributes\Column;
use Framework\Model\Attributes\HasMany;
use Framework\Model\Attributes\PrimaryKey;
use Framework\Model\Attributes\Table;
use Framework\Model\Collection;
use Framework\Model\Model;

#[Table('classes')]
final class SchoolClass extends Model
{
    #[PrimaryKey]
    #[AutoIncrement]
    #[Column(type: 'integer')]
    public int $id;

    #[Column(type: 'text')]
    public string $name;

    #[Column(name: 'join_code', type: 'text')]
    public string $joinCode;

    #[Column(name: 'created_at', type: 'text')]
    public string $createdAt;

    #[Column(name: 'updated_at', type: 'text')]
    public string $updatedAt;

    #[HasMany(Assignment::class, foreignKey: 'class_id', localKey: 'id')]
    public Collection $assignments;

    public static function joinCodeExists(string $joinCode, ?int $exceptId = null): bool
    {
        $code = strtoupper(trim($joinCode));
        $sql = 'SELECT 1 FROM classes WHERE join_code = :join_code';
        $params = ['join_code' => $code];

        if ($exceptId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $exceptId;
        }

        $sql .= ' LIMIT 1';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() !== false;
    }

    public static function generateJoinCode(): string
    {
        do {
            $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        } while (self::joinCodeExists($code));

        return $code;
    }
}
