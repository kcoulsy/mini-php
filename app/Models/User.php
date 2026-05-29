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
    #[Column]
    public int $id;

    #[Column]
    public string $email;

    #[Column]
    public string $password;

    #[Column]
    public string $name;

    #[Column]
    public string $role;

    #[Column]
    public string $createdAt;

    #[Column]
    public string $updatedAt;
}
