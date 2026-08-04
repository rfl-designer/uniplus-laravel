<?php

declare(strict_types=1);

namespace Uniplus\Resources;

/**
 * Resource for financial documents (Documento Financeiro).
 *
 * Endpoint: /public-api/v1/documento-financeiro
 * Supported methods: GET (read-only)
 */
class DocumentoFinanceiro extends ReadOnlyResource
{
    protected string $endpoint = 'public-api/v1/documento-financeiro';

    protected string $primaryKey = 'idDocumento';

    /**
     * Get the boleto (payment slip) for a financial document.
     *
     * @return array<string, mixed>
     */
    public function boleto(string $idDocumento): array
    {
        $response = $this->client->get("{$this->endpoint}/{$idDocumento}/boleto");

        /** @var array<string, mixed> $data */
        $data = $response->json() ?? [];

        return $data;
    }
}
