<?php

declare(strict_types=1);

namespace Uniplus\Resources;

use Uniplus\Exceptions\UniplusException;
use Uniplus\Query\Builder;

/**
 * Service orders resource.
 *
 * Endpoint: /public-api/v1/ordem-servico
 * Methods: GET, POST, PUT
 *
 * The contract has no DELETE /ordem-servico; item removal is the only
 * DELETE-with-body operation and is handled by the dedicated removeItems().
 */
class OrdemServico extends Resource
{
    protected string $endpoint = 'public-api/v1/ordem-servico';

    protected string $primaryKey = 'codigo';

    protected ?string $wrapper = 'ordemServico';

    /**
     * Service order status constants.
     */
    public const STATUS_ABERTA = 1;

    public const STATUS_EM_EXECUCAO = 2;

    public const STATUS_FINALIZADA = 3;

    public const STATUS_CANCELADA = 4;

    public const STATUS_FATURADA = 5;

    public const STATUS_AGENDADA = 6;

    public const STATUS_PAUSADA = 7;

    public const STATUS_PASSOU_PELO_PDV = 8;

    public const STATUS_FATURADA_POR_DAV_OS = 9;

    public const STATUS_MESCLADO = 10;

    public const STATUS_DUPLICADO = 11;

    public const STATUS_SERVICO_NAO_EXECUTADO = 12;

    public const STATUS_ORCAMENTO = 13;

    public const STATUS_FATURADO_PARCIALMENTE = 14;

    public const STATUS_RETIRADA = 15;

    /**
     * Find service orders by status.
     */
    public function byStatus(int $status): Builder
    {
        return $this->where('status', $status);
    }

    /**
     * Find open service orders.
     */
    public function open(): Builder
    {
        return $this->byStatus(self::STATUS_ABERTA);
    }

    /**
     * Find service orders in execution.
     */
    public function inExecution(): Builder
    {
        return $this->byStatus(self::STATUS_EM_EXECUCAO);
    }

    /**
     * Find finished service orders.
     */
    public function finished(): Builder
    {
        return $this->byStatus(self::STATUS_FINALIZADA);
    }

    /**
     * Find canceled service orders.
     */
    public function canceled(): Builder
    {
        return $this->byStatus(self::STATUS_CANCELADA);
    }

    /**
     * Find invoiced service orders.
     */
    public function invoiced(): Builder
    {
        return $this->byStatus(self::STATUS_FATURADA);
    }

    /**
     * Find scheduled service orders.
     */
    public function scheduled(): Builder
    {
        return $this->byStatus(self::STATUS_AGENDADA);
    }

    /**
     * Find paused service orders.
     */
    public function paused(): Builder
    {
        return $this->byStatus(self::STATUS_PAUSADA);
    }

    /**
     * Find service orders by client.
     */
    public function byClient(string $clientCode): Builder
    {
        return $this->where('codigoCliente', $clientCode);
    }

    /**
     * Find service orders by client ID.
     */
    public function byClientId(int $clientId): Builder
    {
        return $this->where('idCliente', $clientId);
    }

    /**
     * Find service orders by branch.
     */
    public function byBranch(string $branchCode): Builder
    {
        return $this->where('codigoFilial', $branchCode);
    }

    /**
     * Find service orders by technician.
     */
    public function byTechnician(int $technicianId): Builder
    {
        return $this->where('idAtendente', $technicianId);
    }

    /**
     * Find service orders by date range.
     */
    public function byDateRange(string $startDate, string $endDate): Builder
    {
        return $this->where('dataOrdemServico', '>=', $startDate)
            ->where('dataOrdemServico', '<=', $endDate);
    }

    /**
     * Find service orders changed after a specific timestamp.
     *
     * @param  int  $timestamp  Unix timestamp in milliseconds
     */
    public function changedAfter(int $timestamp): Builder
    {
        return $this->where('currentTimeMillis', '>=', $timestamp);
    }

    /**
     * Change the status of a service order via the dedicated route.
     *
     * @return array<string, mixed>
     */
    public function changeStatus(string $codigo, int $status, ?string $id = null): array
    {
        $data = $id === null
            ? ['codigo' => $codigo, 'status' => $status]
            : ['id' => $id, 'codigo' => $codigo, 'status' => $status];

        $response = $this->client->post("{$this->endpoint}/alterar-status", $data);

        /** @var array<string, mixed> $result */
        $result = $response->json() ?? [];

        return $result;
    }

    /**
     * Add items to a service order.
     *
     * @param  array<int, array<string, mixed>>  $itens
     * @param  array<string, mixed>  $additionalData  Optional discount fields per the contract
     * @return array<string, mixed>
     */
    public function addItems(string $codigo, array $itens, array $additionalData = []): array
    {
        $data = array_merge(['codigo' => $codigo, 'itens' => $itens], $additionalData);

        $response = $this->client->post("{$this->endpoint}/item", $data);

        /** @var array<string, mixed> $result */
        $result = $response->json() ?? [];

        return $result;
    }

    /**
     * Update items of a service order.
     *
     * @param  array<int, array<string, mixed>>  $itens
     * @param  array<string, mixed>  $additionalData  Optional discount fields per the contract
     * @return array<string, mixed>
     */
    public function updateItems(string $codigo, array $itens, array $additionalData = []): array
    {
        $data = array_merge(['codigo' => $codigo, 'itens' => $itens], $additionalData);

        $response = $this->client->put("{$this->endpoint}/item", $data);

        /** @var array<string, mixed> $result */
        $result = $response->json() ?? [];

        return $result;
    }

    /**
     * Remove items from a service order.
     *
     * This is the contract's only DELETE-with-body operation; it is a
     * dedicated call and does not go through the generic delete().
     *
     * @param  array<int, mixed>  $itens
     */
    public function removeItems(string $codigo, array $itens): bool
    {
        $response = $this->client->delete("{$this->endpoint}/item", [
            'codigo' => $codigo,
            'itens' => $itens,
        ]);

        return $response->successful();
    }

    /**
     * Delete is not supported for service orders.
     *
     * @throws UniplusException
     */
    public function delete(string $code): bool
    {
        throw new UniplusException('Delete operation is not supported for OrdemServico — the contract has no DELETE /ordem-servico.');
    }
}
