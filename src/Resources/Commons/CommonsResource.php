<?php

declare(strict_types=1);

namespace Uniplus\Resources\Commons;

use Illuminate\Support\Collection;
use Uniplus\Exceptions\UniplusException;
use Uniplus\Http\Client;
use Uniplus\Query\Builder;

/**
 * Base class for Commons API resources.
 *
 * Commons endpoints are read-only (GET) and return data directly from
 * database tables. Each endpoint corresponds to a specific table.
 *
 * Pattern: /public-api/v1/commons/{table}
 */
class CommonsResource
{
    protected Client $client;

    /**
     * The table name for this Commons resource.
     */
    protected string $table;

    /**
     * Base endpoint for Commons API.
     */
    protected string $baseEndpoint = 'public-api/v1/commons';

    /**
     * The primary key field name.
     */
    protected string $primaryKey = 'id';

    public function __construct(Client $client, string $table)
    {
        $this->client = $client;
        $this->table = $table;
    }

    /**
     * Get the full endpoint for this Commons resource.
     */
    public function getEndpoint(): string
    {
        return "{$this->baseEndpoint}/{$this->table}";
    }

    /**
     * Get all records from this Commons table.
     *
     * @param  array<string, mixed>  $params
     * @return Collection<int, array<string, mixed>>
     */
    public function all(array $params = []): Collection
    {
        $response = $this->client->get($this->getEndpoint(), $params);

        /** @var array<int, array<string, mixed>> $data */
        $data = $response->json() ?? [];

        return collect($data);
    }

    /**
     * Find a record by its ID.
     *
     * @return array<string, mixed>
     */
    public function find(int|string $id): array
    {
        $response = $this->client->get("{$this->getEndpoint()}/{$id}");

        /** @var array<string, mixed> $data */
        $data = $response->json() ?? [];

        return $data;
    }

    /**
     * Create a new query builder instance.
     */
    public function query(): Builder
    {
        return new Builder($this->client, $this->getEndpoint());
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
     * Get the table name.
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get the HTTP client.
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Create is not supported for Commons resources.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws UniplusException
     */
    public function create(array $data): array
    {
        throw new UniplusException('Create operation is not supported for Commons resources.');
    }

    /**
     * Update is not supported for Commons resources.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws UniplusException
     */
    public function update(array $data): array
    {
        throw new UniplusException('Update operation is not supported for Commons resources.');
    }

    /**
     * Delete is not supported for Commons resources.
     *
     * @throws UniplusException
     */
    public function delete(string $code): bool
    {
        throw new UniplusException('Delete operation is not supported for Commons resources.');
    }
}
