<?php

declare(strict_types=1);

namespace Uniplus\Resources;

/**
 * Resource for entity occurrences (Ocorrências de Entidades).
 *
 * Endpoint: /public-api/v1/entidades-ocorrencias
 * Supported methods: GET (read-only)
 */
class EntidadeOcorrencia extends ReadOnlyResource
{
    protected string $endpoint = 'public-api/v1/entidades-ocorrencias';

    protected string $primaryKey = 'codigo';
}
