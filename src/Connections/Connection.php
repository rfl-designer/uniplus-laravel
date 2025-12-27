<?php

declare(strict_types=1);

namespace Uniplus\Connections;

use Uniplus\Contracts\ConnectionInterface;

abstract class Connection implements ConnectionInterface
{
    protected string $name;

    protected string $account;

    protected string $authorizationCode;

    protected int $userId;

    protected int $branchId;

    protected ?string $baseUrl = null;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(string $name, array $config)
    {
        $this->name = $name;

        /** @var string $account */
        $account = $config['account'] ?? '';
        $this->account = $account;

        /** @var string $authCode */
        $authCode = $config['authorization_code'] ?? '';
        $this->authorizationCode = $authCode;

        /** @var int|string $userId */
        $userId = $config['user_id'] ?? 1;
        $this->userId = (int) $userId;

        /** @var int|string $branchId */
        $branchId = $config['branch_id'] ?? 1;
        $this->branchId = (int) $branchId;
    }

    public function getAccount(): string
    {
        return $this->account;
    }

    public function getAuthorizationCode(): string
    {
        return $this->authorizationCode;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getBranchId(): int
    {
        return $this->branchId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    abstract public function getBaseUrl(): string;
}
