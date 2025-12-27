<?php

declare(strict_types=1);

namespace Uniplus\Resources;

use Uniplus\Exceptions\UniplusException;
use Uniplus\Query\Builder;

/**
 * Product variations (grid/size/color) resource.
 *
 * Endpoint: /public-api/v1/variacoes
 * Methods: GET, POST, PUT
 */
class Variacao extends Resource
{
    protected string $endpoint = 'public-api/v1/variacoes';

    protected string $primaryKey = 'variacao';

    /**
     * Variation type constants.
     */
    public const TIPO_LINHA = 0;

    public const TIPO_COLUNA = 1;

    /**
     * Find variations by product code.
     */
    public function byProduct(string $productCode): Builder
    {
        return $this->where('produto', $productCode);
    }

    /**
     * Find variations by grid code.
     */
    public function byGrid(string $gridCode): Builder
    {
        return $this->where('codigoGrade', $gridCode);
    }

    /**
     * Find variations by type (row or column).
     */
    public function byType(int $type): Builder
    {
        return $this->where('tipoRegistro', $type);
    }

    /**
     * Find row variations.
     */
    public function rows(): Builder
    {
        return $this->byType(self::TIPO_LINHA);
    }

    /**
     * Find column variations.
     */
    public function columns(): Builder
    {
        return $this->byType(self::TIPO_COLUNA);
    }

    /**
     * Find variations by description.
     */
    public function byDescription(string $description): Builder
    {
        return $this->where('descricao', $description);
    }

    /**
     * Add a new variation to a product.
     *
     * @param  array<string, mixed>  $data  Should contain: produto, codigoGrade, descricao, ordem, tipoRegistro
     * @return array<string, mixed>
     */
    public function addVariation(array $data): array
    {
        return $this->create(['variacao' => $data]);
    }

    /**
     * Update a variation.
     *
     * @param  array<string, mixed>  $data  Should contain: produto, variacao, and fields to update
     * @return array<string, mixed>
     */
    public function updateVariation(array $data): array
    {
        return $this->update(['variacao' => $data]);
    }

    /**
     * Delete is not supported for variations.
     *
     * @throws UniplusException
     */
    public function delete(string $code): bool
    {
        throw new UniplusException('Delete operation is not supported for Variacoes.');
    }
}
