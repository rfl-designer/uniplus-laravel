<?php

declare(strict_types=1);

namespace Uniplus\Resources;

use Illuminate\Support\Collection;
use Uniplus\Contracts\ResourceInterface;
use Uniplus\Http\Client;
use Uniplus\Query\Builder;

abstract class Resource implements ResourceInterface
{
    protected Client $client;

    /**
     * The API endpoint for this resource (e.g., 'public-api/v1/produtos').
     */
    protected string $endpoint;

    /**
     * The primary key field name.
     */
    protected string $primaryKey = 'codigo';

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get all resources.
     *
     * @param  array<string, mixed>  $params
     * @return Collection<int, array<string, mixed>>
     */
    public function all(array $params = []): Collection
    {
        $response = $this->client->get($this->endpoint, $params);

        /** @var array<int, array<string, mixed>> $data */
        $data = $response->json() ?? [];

        return collect($data);
    }

    /**
     * Find a resource by its code/identifier.
     *
     * @return array<string, mixed>
     */
    public function find(string $code): array
    {
        $response = $this->client->get("{$this->endpoint}/{$code}");

        /** @var array<string, mixed> $data */
        $data = $response->json() ?? [];

        return $data;
    }

    /**
     * Create a new resource.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $response = $this->client->post($this->endpoint, $data);

        /** @var array<string, mixed> $result */
        $result = $response->json() ?? [];

        return $result;
    }

    /**
     * Update an existing resource.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(array $data): array
    {
        $response = $this->client->put($this->endpoint, $data);

        /** @var array<string, mixed> $result */
        $result = $response->json() ?? [];

        return $result;
    }

    /**
     * Delete a resource by its code.
     */
    public function delete(string $code): bool
    {
        $response = $this->client->delete($this->endpoint, [
            $this->primaryKey => $code,
        ]);

        return $response->successful();
    }

    /**
     * Create a new query builder instance.
     */
    public function query(): Builder
    {
        return new Builder($this->client, $this->endpoint);
    }

    /**
     * Add a where clause and return a query builder.
     */
    public function where(string $field, mixed $operatorOrValue, mixed $value = null): Builder
    {
        return $this->query()->where($field, $operatorOrValue, $value);
    }

    /**
     * Set limit and return a query builder.
     */
    public function limit(int $limit): Builder
    {
        return $this->query()->limit($limit);
    }

    /**
     * Set offset and return a query builder.
     */
    public function offset(int $offset): Builder
    {
        return $this->query()->offset($offset);
    }

    /**
     * Get the HTTP client.
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Get the endpoint.
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }
}
