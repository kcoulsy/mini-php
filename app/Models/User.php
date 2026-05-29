<?php

declare(strict_types=1);

namespace App\Models;

use Framework\Model\Attributes\AutoIncrement;
use Framework\Model\Attributes\Column;
use Framework\Model\Attributes\PrimaryKey;
use Framework\Model\Attributes\Table;
use Framework\Model\Model;

#[Table('users')]
final class User extends Model
{
    public const ROLE_STUDENT = 'student';
    public const ROLE_TEACHER = 'teacher';
    public const ROLE_ADMIN = 'admin';

    /** @var list<string> */
    public const ROLES = [self::ROLE_STUDENT, self::ROLE_TEACHER, self::ROLE_ADMIN];

    #[PrimaryKey]
    #[AutoIncrement]
    #[Column(type: 'integer')]
    public int $id;

    #[Column(type: 'text')]
    public string $email;

    #[Column(type: 'text')]
    public string $password;

    #[Column(type: 'text')]
    public string $name;

    #[Column(type: 'text')]
    public string $role;

    #[Column(name: 'created_at', type: 'text')]
    public string $createdAt;

    #[Column(name: 'updated_at', type: 'text')]
    public string $updatedAt;
}
