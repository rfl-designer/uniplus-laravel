<?php

declare(strict_types=1);

namespace Uniplus\Resources;

class Produto extends Resource
{
    protected string $endpoint = 'public-api/v1/produtos';

    protected string $primaryKey = 'codigo';

    /**
     * Find products changed after a specific timestamp.
     *
     * @param  int  $timestamp  Unix timestamp in milliseconds
     */
    public function changedAfter(int $timestamp): \Uniplus\Query\Builder
    {
        return $this->where('currentTimeMillis', '>=', $timestamp);
    }

    /**
     * Find active products only.
     */
    public function active(): \Uniplus\Query\Builder
    {
        return $this->where('inativo', 0);
    }

    /**
     * Find inactive products only.
     */
    public function inactive(): \Uniplus\Query\Builder
    {
        return $this->where('inativo', 1);
    }

    /**
     * Find products by group.
     */
    public function byGroup(string $groupCode): \Uniplus\Query\Builder
    {
        return $this->where('codigoGrupoProduto', $groupCode);
    }

    /**
     * Find products by brand.
     */
    public function byBrand(string $brandCode): \Uniplus\Query\Builder
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
}
