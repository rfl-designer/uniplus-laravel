<?php

declare(strict_types=1);

namespace Uniplus\Resources;

use Illuminate\Support\Collection;
use Uniplus\Query\Builder;

class Dav extends Resource
{
    protected string $endpoint = 'public-api/v1/davs';

    protected string $primaryKey = 'codigo';

    protected ?string $wrapper = 'dav';

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

    /**
     * List the items of a DAV.
     *
     * @param  array<string, mixed>  $params  Pagination params (offset, limit)
     * @return Collection<int, array<string, mixed>>
     */
    public function items(string $codigoDAV, string $nrItem, array $params = []): Collection
    {
        $response = $this->client->get("{$this->endpoint}/{$codigoDAV}/item/{$nrItem}", $params);

        /** @var array<int, array<string, mixed>> $data */
        $data = $response->json() ?? [];

        return collect($data);
    }

    /**
     * Get a single item of a DAV by its code.
     *
     * @return array<string, mixed>
     */
    public function findItem(string $codigoDAV, string $nrItem, string $codigo): array
    {
        $response = $this->client->get("{$this->endpoint}/{$codigoDAV}/item/{$nrItem}/{$codigo}");

        /** @var array<string, mixed> $data */
        $data = $response->json() ?? [];

        return $data;
    }

    /**
     * Update the quantity of an item.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateItemQuantity(string $codigoDAV, string $nrItem, int $tipoDocumento, array $data): array
    {
        $response = $this->client->put("{$this->endpoint}/{$codigoDAV}/item/{$nrItem}/quantidade/{$tipoDocumento}", $data);

        /** @var array<string, mixed> $result */
        $result = $response->json() ?? [];

        return $result;
    }

    /**
     * Delete an item.
     */
    public function deleteItem(string $codigoDAV, string $nrItem, int $tipoDocumento): bool
    {
        $response = $this->client->delete("{$this->endpoint}/{$codigoDAV}/item/{$nrItem}/quantidade/{$tipoDocumento}");

        return $response->successful();
    }
}
