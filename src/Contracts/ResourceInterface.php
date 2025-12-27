<?php

declare(strict_types=1);

namespace Uniplus\Contracts;

use Illuminate\Support\Collection;
use Uniplus\Query\Builder;

interface ResourceInterface
{
    /**
     * Get all resources.
     *
     * @param  array<string, mixed>  $params
     * @return Collection<int, array<string, mixed>>
     */
    public function all(array $params = []): Collection;

    /**
     * Find a resource by its code/identifier.
     *
     * @return array<string, mixed>
     */
    public function find(string $code): array;

    /**
     * Create a new resource.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(array $data): array;

    /**
     * Update an existing resource.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(array $data): array;

    /**
     * Delete a resource by its code.
     */
    public function delete(string $code): bool;

    /**
     * Create a new query builder instance.
     */
    public function query(): Builder;
}
