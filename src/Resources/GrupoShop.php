<?php

declare(strict_types=1);

namespace Uniplus\Resources;

use Uniplus\Query\Builder;

/**
 * E-commerce categories resource.
 *
 * Endpoint: /public-api/v1/grupo-shop
 * Methods: GET
 */
class GrupoShop extends ReadOnlyResource
{
    protected string $endpoint = 'public-api/v1/grupo-shop';

    protected string $primaryKey = 'id';

    /**
     * Find categories by parent ID.
     */
    public function byParent(int $parentId): Builder
    {
        return $this->where('idPai', $parentId);
    }

    /**
     * Find root categories (no parent).
     */
    public function roots(): Builder
    {
        return $this->where('idPai', 0);
    }

    /**
     * Find category by code.
     */
    public function byCode(string $code): Builder
    {
        return $this->where('codigo', $code);
    }

    /**
     * Find category by name.
     */
    public function byName(string $name): Builder
    {
        return $this->where('nome', $name);
    }
}
