<?php

declare(strict_types=1);

namespace Uniplus\Resources;

use Uniplus\Query\Builder;

/**
 * Entry invoice items resource.
 *
 * Endpoint: /public-api/v1/item-nota-entrada
 * Methods: GET
 */
class ItemNotaEntrada extends ReadOnlyResource
{
    protected string $endpoint = 'public-api/v1/item-nota-entrada';

    protected string $primaryKey = 'idItem';

    /**
     * Item type constants.
     */
    public const TIPO_PRODUTO = 'P';

    public const TIPO_SERVICO = 'S';

    /**
     * Invoice status constants.
     */
    public const STATUS_NORMAL = 0;

    public const STATUS_NFE_AUTORIZADA = 3;

    public const STATUS_NFE_REJEITADA = 4;

    public const STATUS_NFE_NEGADA = 5;

    public const STATUS_NFSE_AUTORIZADA = 6;

    public const STATUS_NFSE_REJEITADA = 7;

    public const STATUS_NFE_CONTINGENCIA = 8;

    public const STATUS_AGUARDANDO_PROCESSAMENTO = 9;

    public const STATUS_PROBLEMA_PROCESSAMENTO = 10;

    public const STATUS_PROBLEMA_ENVIO_LOTE = 11;

    public const STATUS_AGUARDANDO_RETORNO = 14;

    public const STATUS_NFE_CONTINGENCIA_EPEC = 16;

    public const STATUS_NFE_PENDENTE_ESTORNO = 17;

    /**
     * Find items by invoice ID.
     */
    public function byInvoice(int $invoiceId): Builder
    {
        return $this->where('idNotaFiscal', $invoiceId);
    }

    /**
     * Find items by invoice number.
     */
    public function byInvoiceNumber(string $number): Builder
    {
        return $this->where('numeroNotaFiscal', $number);
    }

    /**
     * Find items by product code.
     */
    public function byProduct(string $productCode): Builder
    {
        return $this->where('produtoCodigo', $productCode);
    }

    /**
     * Find items by supplier code.
     */
    public function bySupplier(string $supplierCode): Builder
    {
        return $this->where('fornecedorCodigo', $supplierCode);
    }

    /**
     * Find items by branch.
     */
    public function byBranch(string $branchCode): Builder
    {
        return $this->where('filialCodigo', $branchCode);
    }

    /**
     * Find items by item type (product or service).
     */
    public function byItemType(string $type): Builder
    {
        return $this->where('tipoItem', $type);
    }

    /**
     * Find product items only.
     */
    public function products(): Builder
    {
        return $this->byItemType(self::TIPO_PRODUTO);
    }

    /**
     * Find service items only.
     */
    public function services(): Builder
    {
        return $this->byItemType(self::TIPO_SERVICO);
    }

    /**
     * Find items by CFOP.
     */
    public function byCfop(string $cfop): Builder
    {
        return $this->where('cfopItem', $cfop);
    }

    /**
     * Find items by date range.
     */
    public function byDateRange(string $startDate, string $endDate): Builder
    {
        return $this->where('dataEntrada', '>=', $startDate)
            ->where('dataEntrada', '<=', $endDate);
    }

    /**
     * Find items by invoice status.
     */
    public function byStatus(int $status): Builder
    {
        return $this->where('status', $status);
    }

    /**
     * Find items from authorized invoices.
     */
    public function authorized(): Builder
    {
        return $this->byStatus(self::STATUS_NFE_AUTORIZADA);
    }

    /**
     * Find items that are devolution.
     */
    public function devolutions(): Builder
    {
        return $this->where('notaFiscalDevolucao', 'S');
    }

    /**
     * Find items that are not devolution.
     */
    public function notDevolutions(): Builder
    {
        return $this->where('notaFiscalDevolucao', 'N');
    }
}
