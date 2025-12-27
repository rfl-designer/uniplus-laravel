<?php

declare(strict_types=1);

namespace Uniplus\Resources;

use Uniplus\Query\Builder;

class VendaItem extends Resource
{
    protected string $endpoint = 'public-api/v2/venda-item';

    protected string $primaryKey = 'idItem';

    /**
     * Item types.
     */
    public const TIPO_PRODUTO = 'P';

    public const TIPO_SERVICO = 'S';

    /**
     * Find items by sale ID.
     */
    public function bySale(int $saleId): Builder
    {
        return $this->where('idVenda', $saleId);
    }

    /**
     * Find items by product.
     */
    public function byProduct(string $productCode): Builder
    {
        return $this->where('codigoProduto', $productCode);
    }

    /**
     * Find items by branch.
     */
    public function byBranch(int $branchId): Builder
    {
        return $this->where('codigoFilial', $branchId);
    }

    /**
     * Find items by client.
     */
    public function byClient(string $clientCode): Builder
    {
        return $this->where('codigoCliente', $clientCode);
    }

    /**
     * Find items by seller.
     */
    public function bySeller(string $sellerCode): Builder
    {
        return $this->where('codigoVendedor', $sellerCode);
    }

    /**
     * Find items by type.
     */
    public function byType(string $type): Builder
    {
        return $this->where('tipoItem', $type);
    }

    /**
     * Find product items only.
     */
    public function products(): Builder
    {
        return $this->byType(self::TIPO_PRODUTO);
    }

    /**
     * Find service items only.
     */
    public function services(): Builder
    {
        return $this->byType(self::TIPO_SERVICO);
    }

    /**
     * Find items by date range.
     */
    public function byDateRange(string $startDate, string $endDate): Builder
    {
        return $this->where('emissao', '>=', $startDate)
            ->where('emissao', '<=', $endDate);
    }

    /**
     * Find items by product family.
     */
    public function byFamily(string $familyCode): Builder
    {
        return $this->where('codigoFamilia', $familyCode);
    }

    /**
     * Find items by product group.
     */
    public function byGroup(string $groupCode): Builder
    {
        return $this->where('codigoGrupo', $groupCode);
    }

    /**
     * Find items by brand.
     */
    public function byBrand(string $brandCode): Builder
    {
        return $this->where('codigoMarca', $brandCode);
    }

    /**
     * Note: This resource is read-only (GET only).
     */
    public function create(array $data): array
    {
        throw new \Uniplus\Exceptions\UniplusException(
            'VendaItem resource is read-only.'
        );
    }

    public function update(array $data): array
    {
        throw new \Uniplus\Exceptions\UniplusException(
            'VendaItem resource is read-only.'
        );
    }

    public function delete(string $code): bool
    {
        throw new \Uniplus\Exceptions\UniplusException(
            'VendaItem resource is read-only.'
        );
    }
}
