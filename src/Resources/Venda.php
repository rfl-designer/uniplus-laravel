<?php

declare(strict_types=1);

namespace Uniplus\Resources;

use Uniplus\Query\Builder;

class Venda extends Resource
{
    protected string $endpoint = 'public-api/v2/venda';

    protected string $primaryKey = 'idVenda';

    /**
     * Status types.
     */
    public const STATUS_ABERTO = 0;

    public const STATUS_PROCESSANDO = 1;

    public const STATUS_CONCLUIDO = 3;

    public const STATUS_CANCELADO = 4;

    /**
     * Find sales by client.
     */
    public function byClient(string $clientCode): Builder
    {
        return $this->where('codigoCliente', $clientCode);
    }

    /**
     * Find sales by branch.
     */
    public function byBranch(int $branchId): Builder
    {
        return $this->where('codigoFilial', $branchId);
    }

    /**
     * Find sales by PDV.
     */
    public function byPdv(int $pdv): Builder
    {
        return $this->where('pdv', $pdv);
    }

    /**
     * Find sales by status.
     */
    public function byStatus(int $status): Builder
    {
        return $this->where('status', $status);
    }

    /**
     * Find completed sales.
     */
    public function completed(): Builder
    {
        return $this->byStatus(self::STATUS_CONCLUIDO);
    }

    /**
     * Find canceled sales.
     */
    public function canceled(): Builder
    {
        return $this->byStatus(self::STATUS_CANCELADO);
    }

    /**
     * Find sales by date.
     */
    public function byDate(string $date): Builder
    {
        return $this->where('emissao', $date);
    }

    /**
     * Find sales by date range.
     */
    public function byDateRange(string $startDate, string $endDate): Builder
    {
        return $this->where('emissao', '>=', $startDate)
            ->where('emissao', '<=', $endDate);
    }

    /**
     * Find sales by document number.
     */
    public function byDocument(string $document): Builder
    {
        return $this->where('documento', $document);
    }

    /**
     * Note: This resource is read-only (GET only).
     * Create, update, and delete operations are not supported.
     */
    public function create(array $data): array
    {
        throw new \Uniplus\Exceptions\UniplusException(
            'Venda resource is read-only. Use DAV to create sales.'
        );
    }

    public function update(array $data): array
    {
        throw new \Uniplus\Exceptions\UniplusException(
            'Venda resource is read-only.'
        );
    }

    public function delete(string $code): bool
    {
        throw new \Uniplus\Exceptions\UniplusException(
            'Venda resource is read-only.'
        );
    }
}
