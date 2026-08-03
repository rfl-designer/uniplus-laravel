<?php

declare(strict_types=1);

namespace Uniplus\Resources;

use Uniplus\Query\Builder;

/**
 * Additional EANs (barcodes) resource.
 *
 * Endpoint: /public-api/v1/eans
 * Methods: GET, POST, PUT, DELETE
 */
class Ean extends Resource
{
    protected string $endpoint = 'public-api/v1/eans';

    protected string $primaryKey = 'ean';

    protected ?string $wrapper = 'ean';

    /**
     * Find EANs by product code.
     */
    public function byProduct(string $productCode): Builder
    {
        return $this->where('produto', $productCode);
    }

    /**
     * Find EAN by barcode.
     */
    public function byBarcode(string $barcode): Builder
    {
        return $this->where('ean', $barcode);
    }

    /**
     * Find EANs by variation.
     */
    public function byVariation(string $variation): Builder
    {
        return $this->where('variacao', $variation);
    }

    /**
     * Add a new EAN to a product.
     *
     * @param  array<string, mixed>  $data  Should contain: produto, ean, variacao (optional)
     * @return array<string, mixed>
     */
    public function addEan(array $data): array
    {
        return $this->create($data);
    }

    /**
     * Remove an EAN by barcode.
     */
    public function removeEan(string $barcode): bool
    {
        return $this->delete($barcode);
    }
}
