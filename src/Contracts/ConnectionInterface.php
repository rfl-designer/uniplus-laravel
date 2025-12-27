<?php

declare(strict_types=1);

namespace Uniplus\Contracts;

interface ConnectionInterface
{
    /**
     * Get the account name for this connection.
     */
    public function getAccount(): string;

    /**
     * Get the base URL for API requests.
     */
    public function getBaseUrl(): string;

    /**
     * Get the authorization code (Base64 encoded).
     */
    public function getAuthorizationCode(): string;

    /**
     * Get the user ID for requests.
     */
    public function getUserId(): int;

    /**
     * Get the branch ID for requests.
     */
    public function getBranchId(): int;

    /**
     * Get the connection name.
     */
    public function getName(): string;
}
