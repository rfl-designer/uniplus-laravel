<?php

declare(strict_types=1);

namespace Uniplus\Exceptions;

class AuthenticationException extends UniplusException
{
    public static function invalidCredentials(): self
    {
        return new self('Invalid authorization credentials', 401);
    }

    public static function tokenExpired(): self
    {
        return new self('Access token has expired', 401);
    }

    public static function invalidToken(): self
    {
        return new self('Invalid or malformed access token', 401);
    }
}
