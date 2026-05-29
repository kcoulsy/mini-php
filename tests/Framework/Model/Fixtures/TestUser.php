<?php

declare(strict_types=1);

namespace Tests\Framework\Model\Fixtures;

use Framework\Model\Attributes\AutoIncrement;
use Framework\Model\Attributes\Column;
use Framework\Model\Attributes\PrimaryKey;
use Framework\Model\Attributes\Table;
use Framework\Model\Collection;
use Framework\Model\Model;

#[Table('users')]
final class TestUser extends Model
{
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
