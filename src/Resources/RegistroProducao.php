<?php

declare(strict_types=1);

namespace Uniplus\Resources;

use Uniplus\Exceptions\UniplusException;
use Uniplus\Query\Builder;

/**
 * Production record resource.
 *
 * Endpoint: /public-api/v1/registro-producao
 * Methods: GET, POST
 */
class RegistroProducao extends Resource
{
    protected string $endpoint = 'public-api/v1/registro-producao';

    protected string $primaryKey = 'codigo';

    /**
     * Find production records by branch.
     */
    public function byBranch(string $branchCode): Builder
    {
        return $this->where('codigoFilial', $branchCode);
    }

    /**
     * Find production records by branch ID.
     */
    public function byBranchId(int $branchId): Builder
    {
        return $this->where('idFilial', $branchId);
    }

    /**
     * Find production records by product.
     */
    public function byProduct(int $productId): Builder
    {
        return $this->where('idProduto', $productId);
    }

    /**
     * Find production records by product code.
     */
    public function byProductCode(string $productCode): Builder
    {
        return $this->where('codigoProduto', $productCode);
    }

    /**
     * Find production records by stock location.
     */
    public function byStockLocation(string $locationCode): Builder
    {
        return $this->where('codigoLocalEstoque', $locationCode);
    }

    /**
     * Find production records by user.
     */
    public function byUser(int $userId): Builder
    {
        return $this->where('idUsuario', $userId);
    }

    /**
     * Find production records by date range.
     */
    public function byDateRange(string $startDate, string $endDate): Builder
    {
        return $this->where('dataHora', '>=', $startDate)
            ->where('dataHora', '<=', $endDate);
    }

    /**
     * Create a new production record.
     *
     * @param  array<string, mixed>  $data  Should contain: descricao, itens (array with idProduto, quantidade)
     * @return array<string, mixed>
     */
    public function createRecord(array $data): array
    {
        return $this->create(['registroProducao' => $data]);
    }

    /**
     * Update is not supported for production records.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws UniplusException
     */
    public function update(array $data): array
    {
        throw new UniplusException('Update operation is not supported for RegistroProducao.');
    }

    /**
     * Delete is not supported for production records.
     *
     * @throws UniplusException
     */
    public function delete(string $code): bool
    {
        throw new UniplusException('Delete operation is not supported for RegistroProducao.');
    }
}
