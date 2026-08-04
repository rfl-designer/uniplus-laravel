<?php

declare(strict_types=1);

namespace Uniplus\Resources;

use Illuminate\Support\Collection;
use Uniplus\Query\Builder;

class Produto extends Resource
{
    protected string $endpoint = 'public-api/v1/produtos';

    protected string $primaryKey = 'codigo';

    protected ?string $wrapper = 'produto';

    /**
     * Find products changed after a specific timestamp.
     *
     * @param  int  $timestamp  Unix timestamp in milliseconds
     */
    public function changedAfter(int $timestamp): Builder
    {
        return $this->where('currentTimeMillis', '>=', $timestamp);
    }

    /**
     * Find active products only.
     */
    public function active(): Builder
    {
        return $this->where('inativo', 0);
    }

    /**
     * Find inactive products only.
     */
    public function inactive(): Builder
    {
        return $this->where('inativo', 1);
    }

    /**
     * Find products by group.
     */
    public function byGroup(string $groupCode): Builder
    {
        return $this->where('codigoGrupoProduto', $groupCode);
    }

    /**
     * Find products by brand.
     */
    public function byBrand(string $brandCode): Builder
    {
        return $this->where('codigoMarca', $brandCode);
    }

    /**
     * Update prices for multiple products at once.
     *
     * @param  array<int, array{codigo: string, preco?: float, precos?: array<int, array{filial: string, preco: float, pautasPreco?: array<int, array{codigoPauta: string, preco: float}>}>}>  $products
     * @return array<string, mixed>
     *
     * @throws \InvalidArgumentException
     */
    public function updatePrecos(array $products): array
    {
        if (empty($products)) {
            throw new \InvalidArgumentException('Products array cannot be empty.');
        }

        $response = $this->client->post('public-api/v1/produtos/precos', $products);

        /** @var array<string, mixed> $result */
        $result = $response->json() ?? [];

        return $result;
    }

    /**
     * Create multiple products at once.
     *
     * @param  array<int, array<string, mixed>>  $products
     * @return array<string, mixed>
     *
     * @throws \InvalidArgumentException
     */
    public function createMany(array $products): array
    {
        if (empty($products)) {
            throw new \InvalidArgumentException('Products array cannot be empty.');
        }

        $response = $this->client->post('public-api/v1/produtos/lista', $products);

        /** @var array<string, mixed> $result */
        $result = $response->json() ?? [];

        return $result;
    }

    /**
     * Update multiple products at once.
     *
     * @param  array<int, array<string, mixed>>  $products
     * @return array<string, mixed>
     *
     * @throws \InvalidArgumentException
     */
    public function updateMany(array $products): array
    {
        if (empty($products)) {
            throw new \InvalidArgumentException('Products array cannot be empty.');
        }

        $response = $this->client->put('public-api/v1/produtos/lista', $products);

        /** @var array<string, mixed> $result */
        $result = $response->json() ?? [];

        return $result;
    }

    /**
     * Search products via the dedicated search route.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function search(string $searchParam, ?bool $includeInactiveProducts = null, ?int $branchId = null): Collection
    {
        $query = ['searchParam' => $searchParam];

        if ($includeInactiveProducts !== null) {
            $query['produtosInativos'] = $includeInactiveProducts ? 'true' : 'false';
        }

        if ($branchId !== null) {
            $query['idFilial'] = $branchId;
        }

        $response = $this->client->get("{$this->endpoint}/search", $query);

        /** @var array<int, array<string, mixed>> $data */
        $data = $response->json() ?? [];

        return collect($data);
    }
}
