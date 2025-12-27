<?php

declare(strict_types=1);

namespace Uniplus\Resources;

use Uniplus\Query\Builder;

/**
 * Resource for stock balance by variation (Saldo de Estoque por Variação).
 *
 * Endpoint: /public-api/v2/saldo-estoque/variacao
 * Supported methods: GET
 *
 * Returns a list of stock balances for a product by its variations (color, size, etc.).
 */
class SaldoEstoqueVariacao extends ReadOnlyResource
{
    protected string $endpoint = 'public-api/v2/saldo-estoque/variacao';

    protected string $primaryKey = 'codigo';

    /**
     * Filter stock balance by product code.
     */
    public function byProduct(string $productCode): Builder
    {
        return $this->query()->where('produto', $productCode);
    }

    /**
     * Filter stock balance by branch.
     */
    public function byBranch(string $branchCode): Builder
    {
        return $this->query()->where('filial', $branchCode);
    }

    /**
     * Filter stock balance by product and branch.
     */
    public function byProductAndBranch(string $productCode, string $branchCode): Builder
    {
        return $this->query()
            ->where('produto', $productCode)
            ->where('filial', $branchCode);
    }

    /**
     * Filter stock balance by variation code.
     */
    public function byVariation(string $variationCode): Builder
    {
        return $this->query()->where('variacao', $variationCode);
    }

    /**
     * Filter stock balance by stock location.
     */
    public function byStockLocation(string $locationCode): Builder
    {
        return $this->query()->where('localEstoque', $locationCode);
    }

    /**
     * Get all variations with positive stock.
     */
    public function withStock(): Builder
    {
        return $this->query()->where('saldo', '>', 0);
    }

    /**
     * Get all variations without stock (zero or negative).
     */
    public function withoutStock(): Builder
    {
        return $this->query()->where('saldo', '<=', 0);
    }

    /**
     * Get variations with stock below a minimum quantity.
     */
    public function belowMinimum(float $minQuantity): Builder
    {
        return $this->query()->where('saldo', '<', $minQuantity);
    }

    /**
     * Get variations with stock above a quantity.
     */
    public function aboveQuantity(float $quantity): Builder
    {
        return $this->query()->where('saldo', '>', $quantity);
    }
}
