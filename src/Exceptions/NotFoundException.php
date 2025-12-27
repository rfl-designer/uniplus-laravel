<?php

declare(strict_types=1);

namespace Uniplus\Exceptions;

class NotFoundException extends UniplusException
{
    public static function resource(string $resource, string $identifier): self
    {
        return new self(
            "Resource '{$resource}' with identifier '{$identifier}' not found",
            404
        );
    }
}
