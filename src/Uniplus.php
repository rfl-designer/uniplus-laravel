<?php

declare(strict_types=1);

namespace Uniplus;

use Uniplus\Auth\TokenManager;
use Uniplus\Contracts\ConnectionInterface;
use Uniplus\Http\Client;
use Uniplus\Resources\Dav;
use Uniplus\Resources\Entidade;
use Uniplus\Resources\Produto;
use Uniplus\Resources\SaldoEstoque;
use Uniplus\Resources\Venda;
use Uniplus\Resources\VendaItem;

class Uniplus
{
    protected ConnectionInterface $connection;

    protected Client $client;

    protected TokenManager $tokenManager;

    /** @var array<string, object> */
    protected array $resources = [];

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->tokenManager = new TokenManager($connection);
        $this->client = new Client($connection, $this->tokenManager);
    }

    /**
     * Get the Produto resource.
     */
    public function produtos(): Produto
    {
        return $this->getResource(Produto::class);
    }

    /**
     * Get the Entidade resource.
     */
    public function entidades(): Entidade
    {
        return $this->getResource(Entidade::class);
    }

    /**
     * Get the DAV resource.
     */
    public function davs(): Dav
    {
        return $this->getResource(Dav::class);
    }

    /**
     * Get the SaldoEstoque resource.
     */
    public function saldoEstoque(): SaldoEstoque
    {
        return $this->getResource(SaldoEstoque::class);
    }

    /**
     * Get the Venda resource.
     */
    public function vendas(): Venda
    {
        return $this->getResource(Venda::class);
    }

    /**
     * Get the VendaItem resource.
     */
    public function vendaItens(): VendaItem
    {
        return $this->getResource(VendaItem::class);
    }

    /**
     * Get or create a resource instance.
     *
     * @template T of \Uniplus\Resources\Resource
     *
     * @param  class-string<T>  $class
     * @return T
     */
    protected function getResource(string $class): Resources\Resource
    {
        if (! isset($this->resources[$class])) {
            $this->resources[$class] = new $class($this->client);
        }

        /** @var T */
        return $this->resources[$class];
    }

    /**
     * Get the HTTP client.
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Get the token manager.
     */
    public function getTokenManager(): TokenManager
    {
        return $this->tokenManager;
    }

    /**
     * Get the connection.
     */
    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * Refresh the authentication token.
     */
    public function refreshToken(): void
    {
        $this->tokenManager->refreshToken();
    }
}
