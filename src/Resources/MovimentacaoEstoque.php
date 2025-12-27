<?php

declare(strict_types=1);

namespace Uniplus\Resources;

use Uniplus\Query\Builder;

/**
 * Resource for stock movements (Movimentação de Estoque).
 *
 * Endpoint: /public-api/v2/movimentacao-estoque
 * Supported methods: GET
 *
 * This endpoint returns stock movement records for products.
 */
class MovimentacaoEstoque extends ReadOnlyResource
{
    protected string $endpoint = 'public-api/v2/movimentacao-estoque';

    protected string $primaryKey = 'codigo';

    /**
     * Filter movements by product code.
     */
    public function byProduct(string $productCode): Builder
    {
        return $this->query()->where('produto', $productCode);
    }

    /**
     * Filter movements by branch.
     */
    public function byBranch(string $branchCode): Builder
    {
        return $this->query()->where('filial', $branchCode);
    }

    /**
     * Filter movements by stock location.
     */
    public function byStockLocation(string $locationCode): Builder
    {
        return $this->query()->where('localEstoque', $locationCode);
    }

    /**
     * Filter movements by variation.
     */
    public function byVariation(string $variationCode): Builder
    {
        return $this->query()->where('variacao', $variationCode);
    }

    /**
     * Filter movements by movement type.
     *
     * @param  string  $type  E = Entrada (entry), S = Saída (exit)
     */
    public function byType(string $type): Builder
    {
        return $this->query()->where('tipoMovimentacao', $type);
    }

    /**
     * Filter by entry movements only.
     */
    public function entries(): Builder
    {
        return $this->byType('E');
    }

    /**
     * Filter by exit movements only.
     */
    public function exits(): Builder
    {
        return $this->byType('S');
    }

    /**
     * Filter movements modified since a specific timestamp.
     *
     * @param  int  $timestamp  Unix timestamp in milliseconds
     */
    public function modifiedSince(int $timestamp): Builder
    {
        return $this->query()->where('currenttimemillis', '>=', $timestamp);
    }

    /**
     * Filter movements by date range.
     *
     * @param  string  $startDate  Format: YYYY-MM-DD
     * @param  string  $endDate  Format: YYYY-MM-DD
     */
    public function byDateRange(string $startDate, string $endDate): Builder
    {
        return $this->query()
            ->where('dataMovimentacao', '>=', $startDate)
            ->where('dataMovimentacao', '<=', $endDate);
    }

    /**
     * Filter movements by reason/motive code.
     */
    public function byReason(string $reasonCode): Builder
    {
        return $this->query()->where('motivo', $reasonCode);
    }

    /**
     * Filter movements by operation nature.
     */
    public function byNatureOperation(string $natureCode): Builder
    {
        return $this->query()->where('naturezaOperacao', $natureCode);
    }
}
