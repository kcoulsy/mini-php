<?php

declare(strict_types=1);

namespace App\Data;

use App\Validation\ValidRole;
use Framework\Validation\Attributes\Email;
use Framework\Validation\Attributes\MinLength;
use Framework\Validation\Attributes\Nullable;
use Framework\Validation\Attributes\Required;
use Framework\Validation\Attributes\Trim;

final class UpdateUserData
{
    #[Trim]
    #[Required]
    #[Email]
    public string $email;

    #[Trim]
    #[Nullable]
    public string $name = '';

    #[Required]
    #[ValidRole]
    public string $role;

    #[Trim]
    #[Nullable]
    #[MinLength(8)]
    public ?string $password = null;
}
