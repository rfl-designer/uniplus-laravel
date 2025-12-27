<?php

declare(strict_types=1);

namespace Uniplus\Exceptions;

class ConnectionException extends UniplusException
{
    public static function timeout(string $url): self
    {
        return new self("Connection timeout while connecting to: {$url}", 408);
    }

    public static function unreachable(string $url): self
    {
        return new self("Unable to reach server at: {$url}", 503);
    }

    public static function serverError(string $message, int $code = 500): self
    {
        return new self("Server error: {$message}", $code);
    }
}
