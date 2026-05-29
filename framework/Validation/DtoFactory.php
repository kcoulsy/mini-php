<?php

declare(strict_types=1);

namespace Framework\Validation;

use Framework\Request;

final class DtoFactory
{
    /**
     * @param class-string $dtoClass
     */
    public static function fromRequest(Request $request, string $dtoClass): DtoResult
    {
        return DtoValidator::validate($dtoClass, $request->all());
    }
}
