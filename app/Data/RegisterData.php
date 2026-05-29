<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\User;
use Framework\Validation\Attributes\Confirmed;
use Framework\Validation\Attributes\Unique;
use Framework\Validation\Attributes\Email;
use Framework\Validation\Attributes\MaxLength;
use Framework\Validation\Attributes\MinLength;
use Framework\Validation\Attributes\Nullable;
use Framework\Validation\Attributes\Required;
use Framework\Validation\Attributes\Trim;

final class RegisterData
{
    #[Trim]
    #[Required]
    #[Email]
    #[Unique(User::class)]
    public string $email;

    #[Required]
    #[MinLength(8)]
    #[Confirmed]
    public string $password;

    public string $passwordConfirmation;

    #[Trim]
    #[Nullable]
    #[MaxLength(120)]
    public string $name = '';
}
