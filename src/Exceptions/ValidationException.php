<?php

declare(strict_types=1);

namespace Uniplus\Exceptions;

class ValidationException extends UniplusException
{
    /** @var array<string, array<string>> */
    protected array $errors = [];

    /**
     * @param  array<string, array<string>>  $errors
     */
    public static function withErrors(array $errors, string $message = 'Validation failed'): self
    {
        $exception = new self($message, 422);
        $exception->errors = $errors;

        return $exception;
    }

    /**
     * @return array<string, array<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
