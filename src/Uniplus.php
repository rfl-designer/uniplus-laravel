<?php

declare(strict_types=1);

namespace Uniplus;

use Uniplus\Auth\TokenManager;
use Uniplus\Contracts\ConnectionInterface;
use Uniplus\Http\Client;
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

class Uniplus
{
    protected ConnectionInterface $connection;

    protected Client $client;

    protected TokenManager $tokenManager;

    /** @var array<string, object> */
    protected array $resources = [];

    protected ?CommonsFactory $commonsFactory = null;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->tokenManager = new TokenManager($connection);
        $this->client = new Client($connection, $this->tokenManager);
    }

    // =========================================================================
    // Core Resources (existing)
    // =========================================================================

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

    // =========================================================================
    // Individual Resources (Phase 1)
    // =========================================================================

    /**
     * Get the GrupoShop resource (e-commerce categories).
     */
    public function grupoShop(): GrupoShop
    {
        return $this->getResource(GrupoShop::class);
    }

    /**
     * Get the OrdemServico resource (service orders).
     */
    public function ordemServico(): OrdemServico
    {
        return $this->getResource(OrdemServico::class);
    }

    /**
     * Get the Ean resource (additional EANs).
     */
    public function eans(): Ean
    {
        return $this->getResource(Ean::class);
    }

    /**
     * Get the Embalagem resource (packages).
     */
    public function embalagens(): Embalagem
    {
        return $this->getResource(Embalagem::class);
    }

    /**
     * Get the RegistroProducao resource (production records).
     */
    public function registroProducao(): RegistroProducao
    {
        return $this->getResource(RegistroProducao::class);
    }

    /**
     * Get the Variacao resource (variations/grids).
     */
    public function variacoes(): Variacao
    {
        return $this->getResource(Variacao::class);
    }

    /**
     * Get the ItemNotaEntrada resource (entry invoice items).
     */
    public function itemNotaEntrada(): ItemNotaEntrada
    {
        return $this->getResource(ItemNotaEntrada::class);
    }

    /**
     * Get the ItemNotaEntradaCompra resource (purchase entry invoice items).
     */
    public function itemNotaEntradaCompra(): ItemNotaEntradaCompra
    {
        return $this->getResource(ItemNotaEntradaCompra::class);
    }

    // =========================================================================
    // Sales, Financial and Grouped Resources (Phase 2)
    // =========================================================================

    /**
     * Get the MovimentacaoEstoque resource (stock movements).
     */
    public function movimentacaoEstoque(): MovimentacaoEstoque
    {
        return $this->getResource(MovimentacaoEstoque::class);
    }

    /**
     * Get the TipoDocumentoFinanceiro resource (financial document types).
     */
    public function tipoDocumentoFinanceiro(): TipoDocumentoFinanceiro
    {
        return $this->getResource(TipoDocumentoFinanceiro::class);
    }

    /**
     * Get the SaldoEstoqueVariacao resource (stock balance by variation).
     */
    public function saldoEstoqueVariacao(): SaldoEstoqueVariacao
    {
        return $this->getResource(SaldoEstoqueVariacao::class);
    }

    /**
     * Get the ContaGourmet resource (Gourmet accounts).
     */
    public function contaGourmet(): ContaGourmet
    {
        return $this->getResource(ContaGourmet::class);
    }

    // =========================================================================
    // Commons Resources Factory (Phase 3)
    // =========================================================================

    /**
     * Get the Commons factory for accessing Commons endpoints.
     *
     * Usage:
     * - $uniplus->commons()->banco()->all()
     * - $uniplus->commons()->cidade()->find(1)
     * - $uniplus->commons()->table('estado')->all()
     */
    public function commons(): CommonsFactory
    {
        if ($this->commonsFactory === null) {
            $this->commonsFactory = new CommonsFactory($this->client);
        }

        return $this->commonsFactory;
    }

    // =========================================================================
    // Internal Methods
    // =========================================================================

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
