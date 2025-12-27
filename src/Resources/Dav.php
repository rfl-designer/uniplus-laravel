<?php

declare(strict_types=1);

namespace Uniplus\Resources;

use Uniplus\Query\Builder;

class Dav extends Resource
{
    protected string $endpoint = 'public-api/v1/davs';

    protected string $primaryKey = 'codigo';

    /**
     * Document types.
     */
    public const TIPO_PRE_VENDA = 1;

    public const TIPO_ORCAMENTO = 2;

    public const TIPO_CONSIGNACAO = 3;

    public const TIPO_PEDIDO_VENDA = 4;

    /**
     * Status types.
     */
    public const STATUS_ABERTO = 0;

    public const STATUS_FECHADO = 1;

    public const STATUS_CANCELADO = 2;

    /**
     * Find DAVs by document type.
     */
    public function byType(int $type): Builder
    {
        return $this->where('tipoDocumento', $type);
    }

    /**
     * Find pre-sales.
     */
    public function preSales(): Builder
    {
        return $this->byType(self::TIPO_PRE_VENDA);
    }

    /**
     * Find quotes/budgets.
     */
    public function quotes(): Builder
    {
        return $this->byType(self::TIPO_ORCAMENTO);
    }

    /**
     * Find consignments.
     */
    public function consignments(): Builder
    {
        return $this->byType(self::TIPO_CONSIGNACAO);
    }

    /**
     * Find sales orders.
     */
    public function salesOrders(): Builder
    {
        return $this->byType(self::TIPO_PEDIDO_VENDA);
    }

    /**
     * Find DAVs by status.
     */
    public function byStatus(int $status): Builder
    {
        return $this->where('status', $status);
    }

    /**
     * Find open DAVs.
     */
    public function open(): Builder
    {
        return $this->byStatus(self::STATUS_ABERTO);
    }

    /**
     * Find closed DAVs.
     */
    public function closed(): Builder
    {
        return $this->byStatus(self::STATUS_FECHADO);
    }

    /**
     * Find canceled DAVs.
     */
    public function canceled(): Builder
    {
        return $this->byStatus(self::STATUS_CANCELADO);
    }

    /**
     * Find DAVs by client.
     */
    public function byClient(string $clientCode): Builder
    {
        return $this->where('codigoCliente', $clientCode);
    }

    /**
     * Find DAVs by branch.
     */
    public function byBranch(int $branchId): Builder
    {
        return $this->where('filial', $branchId);
    }

    /**
     * Find DAVs by date range.
     */
    public function byDateRange(string $startDate, string $endDate): Builder
    {
        return $this->where('data', '>=', $startDate)
            ->where('data', '<=', $endDate);
    }
}
