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
}
