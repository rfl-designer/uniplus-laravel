<?php

declare(strict_types=1);

namespace Uniplus\Resources;

use Uniplus\Query\Builder;

/**
 * Service orders resource.
 *
 * Endpoint: /public-api/v1/ordem-servico
 * Methods: GET
 */
class OrdemServico extends ReadOnlyResource
{
    protected string $endpoint = 'public-api/v1/ordem-servico';

    protected string $primaryKey = 'codigo';

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
}
