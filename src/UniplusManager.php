<?php

declare(strict_types=1);

namespace Uniplus;

use Illuminate\Contracts\Foundation\Application;
use Uniplus\Connections\RemoteConnection;
use Uniplus\Exceptions\ConnectionException;
use Uniplus\Resources\Dav;
use Uniplus\Resources\Entidade;
use Uniplus\Resources\Produto;
use Uniplus\Resources\SaldoEstoque;
use Uniplus\Resources\Venda;
use Uniplus\Resources\VendaItem;
use Uniplus\Testing\FakeClient;

class UniplusManager
{
    protected Application $app;

    /** @var array<string, Uniplus> */
    protected array $connections = [];

    protected ?FakeClient $fakeClient = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get a Uniplus instance for a specific connection.
     */
    public function connection(?string $name = null): Uniplus
    {
        $name = $name ?? $this->getDefaultConnection();

        if (! isset($this->connections[$name])) {
            $this->connections[$name] = $this->makeConnection($name);
        }

        return $this->connections[$name];
    }

    /**
     * Create a new connection instance.
     */
    protected function makeConnection(string $name): Uniplus
    {
        $config = $this->getConnectionConfig($name);

        if ($config === null) {
            throw new ConnectionException("Uniplus connection [{$name}] is not configured.");
        }

        $connection = new RemoteConnection($name, $config);

        return new Uniplus($connection);
    }

    /**
     * Get the configuration for a connection.
     *
     * @return array<string, mixed>|null
     */
    protected function getConnectionConfig(string $name): ?array
    {
        /** @var array<string, mixed>|null $config */
        $config = config("uniplus.connections.{$name}");

        return $config;
    }

    /**
     * Get the default connection name.
     */
    public function getDefaultConnection(): string
    {
        /** @var string $default */
        $default = config('uniplus.default', 'default');

        return $default;
    }

    /**
     * Add a new connection dynamically.
     *
     * @param  array<string, mixed>  $config
     */
    public function addConnection(string $name, array $config): self
    {
        // Store in runtime config
        config(["uniplus.connections.{$name}" => $config]);

        // Clear cached connection if exists
        unset($this->connections[$name]);

        return $this;
    }

    /**
     * Remove a connection.
     */
    public function removeConnection(string $name): self
    {
        unset($this->connections[$name]);

        return $this;
    }

    /**
     * Get all connection names.
     *
     * @return array<int, string>
     */
    public function getConnectionNames(): array
    {
        /** @var array<string, mixed> $connections */
        $connections = config('uniplus.connections', []);

        return array_keys($connections);
    }

    /**
     * Purge all cached connections.
     */
    public function purge(): void
    {
        $this->connections = [];
    }

    /**
     * Set up fake responses for testing.
     *
     * @param  array<string, array<string, mixed>>  $responses
     */
    public function fake(array $responses = []): FakeClient
    {
        $this->fakeClient = new FakeClient($responses);

        return $this->fakeClient;
    }

    /**
     * Check if the manager is in fake mode.
     */
    public function isFaking(): bool
    {
        return $this->fakeClient !== null;
    }

    /**
     * Get the fake client.
     */
    public function getFakeClient(): ?FakeClient
    {
        return $this->fakeClient;
    }

    /**
     * Assert that a request was sent.
     */
    public function assertSent(string $method, string $url): void
    {
        if ($this->fakeClient === null) {
            throw new \RuntimeException('Cannot assert requests when not in fake mode.');
        }

        $this->fakeClient->assertSent($method, $url);
    }

    /**
     * Assert that a request was not sent.
     */
    public function assertNotSent(string $url): void
    {
        if ($this->fakeClient === null) {
            throw new \RuntimeException('Cannot assert requests when not in fake mode.');
        }

        $this->fakeClient->assertNotSent($url);
    }

    /**
     * Assert the number of requests sent.
     */
    public function assertSentCount(int $count): void
    {
        if ($this->fakeClient === null) {
            throw new \RuntimeException('Cannot assert requests when not in fake mode.');
        }

        $this->fakeClient->assertSentCount($count);
    }

    // Proxy methods to default connection

    /**
     * Get the Produto resource from the default connection.
     */
    public function produtos(): Produto
    {
        return $this->connection()->produtos();
    }

    /**
     * Get the Entidade resource from the default connection.
     */
    public function entidades(): Entidade
    {
        return $this->connection()->entidades();
    }

    /**
     * Get the DAV resource from the default connection.
     */
    public function davs(): Dav
    {
        return $this->connection()->davs();
    }

    /**
     * Get the SaldoEstoque resource from the default connection.
     */
    public function saldoEstoque(): SaldoEstoque
    {
        return $this->connection()->saldoEstoque();
    }

    /**
     * Get the Venda resource from the default connection.
     */
    public function vendas(): Venda
    {
        return $this->connection()->vendas();
    }

    /**
     * Get the VendaItem resource from the default connection.
     */
    public function vendaItens(): VendaItem
    {
        return $this->connection()->vendaItens();
    }
}
