<?php

declare(strict_types=1);

namespace Uniplus\Resources;

/**
 * Resource for product technical sheets (Ficha Técnica).
 *
 * Endpoint: /public-api/v1/ficha-tecnica
 * Supported methods: GET (read-only)
 */
class FichaTecnica extends ReadOnlyResource
{
    protected string $endpoint = 'public-api/v1/ficha-tecnica';

    protected string $primaryKey = 'codigo';
}
