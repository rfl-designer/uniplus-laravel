<?php

declare(strict_types=1);

namespace Uniplus\Resources;

use Illuminate\Support\Collection;
use Uniplus\Query\Builder;

class SaldoEstoque extends Resource
{
    protected string $endpoint = 'public-api/v2/saldo-estoque';

    protected string $primaryKey = 'produto';

    /**
     * Find stock balance by product.
     */
    public function byProduct(string $productCode): Builder
    {
        return $this->where('produto', $productCode);
    }

    /**
     * Find stock balance by branch.
     */
    public function byBranch(int $branchId): Builder
    {
        return $this->where('filial', $branchId);
    }

    /**
     * Find stock balance by product and branch.
     */
    public function byProductAndBranch(string $productCode, int $branchId): Builder
    {
        return $this->where('produto', $productCode)
            ->where('filial', $branchId);
    }

    /**
     * Get stock balance for a specific product.
     *
     * @return array<string, mixed>|null
     */
    public function getBalance(string $productCode, ?int $branchId = null): ?array
    {
        $query = $this->byProduct($productCode);

        if ($branchId !== null) {
            $query = $query->where('filial', $branchId);
        }

        return $query->first();
    }

    /**
     * Update stock balance.
     *
     * @param  array<string, mixed>  $data  Should contain: produto, quantidade, filial
     * @return array<string, mixed>
     */
    public function updateBalance(array $data): array
    {
        return $this->create($data);
    }

    /**
     * Get stock balances for multiple products.
     *
     * @param  array<string>  $productCodes
     * @return Collection<int, array<string, mixed>>
     */
    public function getBalances(array $productCodes, ?int $branchId = null): Collection
    {
        // The API doesn't support IN operator, so we need to fetch all and filter
        $query = $this->query();

        if ($branchId !== null) {
            $query = $query->where('filial', $branchId);
        }

        $results = $query->get();

        return $results->filter(function (array $item) use ($productCodes): bool {
            $productCode = $item['produto'] ?? $item['codigoProduto'] ?? '';

            return in_array($productCode, $productCodes, true);
        })->values();
    }
}
