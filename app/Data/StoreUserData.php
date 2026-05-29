<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\User;
use App\Validation\ValidRole;
use Framework\Validation\Attributes\Unique;
use Framework\Validation\Attributes\Email;
use Framework\Validation\Attributes\MinLength;
use Framework\Validation\Attributes\Nullable;
use Framework\Validation\Attributes\Required;
use Framework\Validation\Attributes\Trim;

final class StoreUserData
{
    #[Trim]
    #[Required]
    #[Email]
    #[Unique(User::class)]
    public string $email;

    #[Trim]
    #[Nullable]
    public string $name = '';

    #[Required]
    #[ValidRole]
    public string $role;

    #[Required]
    #[MinLength(8)]
    public string $password;
}
