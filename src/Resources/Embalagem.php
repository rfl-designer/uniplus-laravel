<?php

declare(strict_types=1);

namespace Uniplus\Resources;

use Uniplus\Query\Builder;

/**
 * Product packaging resource.
 *
 * Endpoint: /public-api/v1/embalagens
 * Methods: GET, POST, PUT
 */
class Embalagem extends Resource
{
    protected string $endpoint = 'public-api/v1/embalagens';

    protected string $primaryKey = 'id';

    /**
     * Packaging type constants.
     */
    public const TIPO_COMPRA_VENDA = 0;

    public const TIPO_SOMENTE_COMPRA = 1;

    public const TIPO_SOMENTE_VENDA = 2;

    /**
     * Find packaging by product code.
     */
    public function byProduct(string $productCode): Builder
    {
        return $this->where('produto', $productCode);
    }

    /**
     * Find packaging by unit of measure.
     */
    public function byUnitOfMeasure(string $unit): Builder
    {
        return $this->where('unidadeMedida', $unit);
    }

    /**
     * Find packaging by EAN/barcode.
     */
    public function byEan(string $ean): Builder
    {
        return $this->where('ean', $ean);
    }

    /**
     * Find packaging by type.
     */
    public function byType(int $type): Builder
    {
        return $this->where('tipoEmbalagem', $type);
    }

    /**
     * Find packaging for purchase and sale.
     */
    public function forPurchaseAndSale(): Builder
    {
        return $this->byType(self::TIPO_COMPRA_VENDA);
    }

    /**
     * Find packaging for purchase only.
     */
    public function forPurchaseOnly(): Builder
    {
        return $this->byType(self::TIPO_SOMENTE_COMPRA);
    }

    /**
     * Find packaging for sale only.
     */
    public function forSaleOnly(): Builder
    {
        return $this->byType(self::TIPO_SOMENTE_VENDA);
    }

    /**
     * Add packaging to a product.
     *
     * @param  array<string, mixed>  $data  Should contain: produto, unidadeMedida, fatorConversao, preco, ean
     * @return array<string, mixed>
     */
    public function addPackaging(array $data): array
    {
        return $this->create(['embalagem' => $data]);
    }

    /**
     * Update packaging.
     *
     * @param  array<string, mixed>  $data  Should contain: produto, unidadeMedida, and fields to update
     * @return array<string, mixed>
     */
    public function updatePackaging(array $data): array
    {
        return $this->update(['embalagem' => $data]);
    }

    /**
     * Delete is not supported for Embalagens.
     *
     * @throws \Uniplus\Exceptions\UniplusException
     */
    public function delete(string $code): bool
    {
        throw new \Uniplus\Exceptions\UniplusException('Delete operation is not supported for Embalagens.');
    }
}
