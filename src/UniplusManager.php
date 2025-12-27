<?php

declare(strict_types=1);

namespace Uniplus;

use Illuminate\Contracts\Foundation\Application;
use Uniplus\Connections\RemoteConnection;
use Uniplus\Exceptions\ConnectionException;
use Uniplus\Resources\Commons\CommonsFactory;
use Uniplus\Resources\ContaGourmet;
use Uniplus\Resources\Dav;
use Uniplus\Resources\Ean;
use Uniplus\Resources\Embalagem;
use Uniplus\Resources\Entidade;
use Uniplus\Resources\GrupoShop;
use Uniplus\Resources\ItemNotaEntrada;
use Uniplus\Resources\ItemNotaEntradaCompra;
use Uniplus\Resources\MovimentacaoEstoque;
use Uniplus\Resources\OrdemServico;
use Uniplus\Resources\Produto;
use Uniplus\Resources\RegistroProducao;
use Uniplus\Resources\SaldoEstoque;
use Uniplus\Resources\SaldoEstoqueVariacao;
use Uniplus\Resources\TipoDocumentoFinanceiro;
use Uniplus\Resources\Variacao;
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

    // =========================================================================
    // Proxy methods to default connection - Core Resources
    // =========================================================================

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

    // =========================================================================
    // Proxy methods - Individual Resources (Phase 1)
    // =========================================================================

    /**
     * Get the GrupoShop resource from the default connection.
     */
    public function grupoShop(): GrupoShop
    {
        return $this->connection()->grupoShop();
    }

    /**
     * Get the OrdemServico resource from the default connection.
     */
    public function ordemServico(): OrdemServico
    {
        return $this->connection()->ordemServico();
    }

    /**
     * Get the Ean resource from the default connection.
     */
    public function eans(): Ean
    {
        return $this->connection()->eans();
    }

    /**
     * Get the Embalagem resource from the default connection.
     */
    public function embalagens(): Embalagem
    {
        return $this->connection()->embalagens();
    }

    /**
     * Get the RegistroProducao resource from the default connection.
     */
    public function registroProducao(): RegistroProducao
    {
        return $this->connection()->registroProducao();
    }

    /**
     * Get the Variacao resource from the default connection.
     */
    public function variacoes(): Variacao
    {
        return $this->connection()->variacoes();
    }

    /**
     * Get the ItemNotaEntrada resource from the default connection.
     */
    public function itemNotaEntrada(): ItemNotaEntrada
    {
        return $this->connection()->itemNotaEntrada();
    }

    /**
     * Get the ItemNotaEntradaCompra resource from the default connection.
     */
    public function itemNotaEntradaCompra(): ItemNotaEntradaCompra
    {
        return $this->connection()->itemNotaEntradaCompra();
    }

    // =========================================================================
    // Proxy methods - Sales, Financial and Grouped Resources (Phase 2)
    // =========================================================================

    /**
     * Get the MovimentacaoEstoque resource from the default connection.
     */
    public function movimentacaoEstoque(): MovimentacaoEstoque
    {
        return $this->connection()->movimentacaoEstoque();
    }

    /**
     * Get the TipoDocumentoFinanceiro resource from the default connection.
     */
    public function tipoDocumentoFinanceiro(): TipoDocumentoFinanceiro
    {
        return $this->connection()->tipoDocumentoFinanceiro();
    }

    /**
     * Get the SaldoEstoqueVariacao resource from the default connection.
     */
    public function saldoEstoqueVariacao(): SaldoEstoqueVariacao
    {
        return $this->connection()->saldoEstoqueVariacao();
    }

    /**
     * Get the ContaGourmet resource from the default connection.
     */
    public function contaGourmet(): ContaGourmet
    {
        return $this->connection()->contaGourmet();
    }

    // =========================================================================
    // Proxy methods - Commons Resources (Phase 3)
    // =========================================================================

    /**
     * Get the Commons factory from the default connection.
     */
    public function commons(): CommonsFactory
    {
        return $this->connection()->commons();
    }
}
