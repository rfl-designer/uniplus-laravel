<?php

declare(strict_types=1);

namespace Uniplus\Resources;

use Uniplus\Query\Builder;

class Entidade extends Resource
{
    protected string $endpoint = 'public-api/v1/entidades';

    protected string $primaryKey = 'codigo';

    /**
     * Entity types.
     */
    public const TIPO_CLIENTE = 1;

    public const TIPO_FORNECEDOR = 2;

    public const TIPO_TRANSPORTADORA = 3;

    public const TIPO_VENDEDOR = 4;

    public const TIPO_FUNCIONARIO = 5;

    /**
     * Find entities changed after a specific timestamp.
     *
     * @param  int  $timestamp  Unix timestamp in milliseconds
     */
    public function changedAfter(int $timestamp): Builder
    {
        return $this->where('currentTimeMillis', '>=', $timestamp);
    }

    /**
     * Find active entities only.
     */
    public function active(): Builder
    {
        return $this->where('inativo', 0);
    }

    /**
     * Find inactive entities only.
     */
    public function inactive(): Builder
    {
        return $this->where('inativo', 1);
    }

    /**
     * Find entities by type.
     */
    public function byType(int $type): Builder
    {
        return $this->where('tipo', $type);
    }

    /**
     * Find clients only.
     */
    public function clients(): Builder
    {
        return $this->byType(self::TIPO_CLIENTE);
    }

    /**
     * Find suppliers only.
     */
    public function suppliers(): Builder
    {
        return $this->byType(self::TIPO_FORNECEDOR);
    }

    /**
     * Find carriers only.
     */
    public function carriers(): Builder
    {
        return $this->byType(self::TIPO_TRANSPORTADORA);
    }

    /**
     * Find salespeople only.
     */
    public function salespeople(): Builder
    {
        return $this->byType(self::TIPO_VENDEDOR);
    }

    /**
     * Find employees only.
     */
    public function employees(): Builder
    {
        return $this->byType(self::TIPO_FUNCIONARIO);
    }

    /**
     * Find entity by CPF/CNPJ.
     */
    public function byCpfCnpj(string $cpfCnpj): Builder
    {
        return $this->where('cnpjCpf', $cpfCnpj);
    }
}
