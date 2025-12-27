<?php

declare(strict_types=1);

namespace Uniplus\Query;

use Illuminate\Support\Collection;
use Uniplus\Http\Client;

class Builder
{
    protected Client $client;

    protected string $endpoint;

    /** @var array<int, Filter> */
    protected array $filters = [];

    protected ?int $limit = null;

    protected ?int $offset = null;

    public function __construct(Client $client, string $endpoint)
    {
        $this->client = $client;
        $this->endpoint = $endpoint;
    }

    /**
     * Add a where clause to the query.
     *
     * @param  string  $field  The field name
     * @param  mixed  $operatorOrValue  The operator (=, !=, >, >=, <, <=) or value if using default operator
     * @param  mixed  $value  The value (optional if operator is the value)
     */
    public function where(string $field, mixed $operatorOrValue, mixed $value = null): self
    {
        if ($value === null) {
            // Two argument form: where('field', 'value')
            $filterValue = $this->normalizeFilterValue($operatorOrValue);
            $this->filters[] = Filter::make($field, '=', $filterValue);
        } else {
            // Three argument form: where('field', '>=', 'value')
            $operator = is_string($operatorOrValue) ? $operatorOrValue : (is_scalar($operatorOrValue) ? (string) $operatorOrValue : '=');
            $filterValue = $this->normalizeFilterValue($value);
            $this->filters[] = Filter::make($field, $operator, $filterValue);
        }

        return $this;
    }

    /**
     * Normalize a mixed value to a filter-compatible type.
     */
    protected function normalizeFilterValue(mixed $value): string|int|float|bool|\DateTimeInterface|\Stringable|null
    {
        if ($value === null || is_scalar($value) || $value instanceof \DateTimeInterface || $value instanceof \Stringable) {
            return $value;
        }

        return null;
    }

    /**
     * Add a where equal clause.
     */
    public function whereEquals(string $field, mixed $value): self
    {
        return $this->where($field, '=', $value);
    }

    /**
     * Add a where not equal clause.
     */
    public function whereNotEquals(string $field, mixed $value): self
    {
        return $this->where($field, '!=', $value);
    }

    /**
     * Add a where greater than clause.
     */
    public function whereGreaterThan(string $field, mixed $value): self
    {
        return $this->where($field, '>', $value);
    }

    /**
     * Add a where greater than or equal clause.
     */
    public function whereGreaterThanOrEqual(string $field, mixed $value): self
    {
        return $this->where($field, '>=', $value);
    }

    /**
     * Add a where less than clause.
     */
    public function whereLessThan(string $field, mixed $value): self
    {
        return $this->where($field, '<', $value);
    }

    /**
     * Add a where less than or equal clause.
     */
    public function whereLessThanOrEqual(string $field, mixed $value): self
    {
        return $this->where($field, '<=', $value);
    }

    /**
     * Set the limit for results.
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Alias for limit().
     */
    public function take(int $count): self
    {
        return $this->limit($count);
    }

    /**
     * Set the offset for results.
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Alias for offset().
     */
    public function skip(int $count): self
    {
        return $this->offset($count);
    }

    /**
     * Execute the query and get results.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function get(): Collection
    {
        $queryParams = $this->buildQueryParams();

        $response = $this->client->get($this->endpoint, $queryParams);

        /** @var array<int, array<string, mixed>> $data */
        $data = $response->json() ?? [];

        return collect($data);
    }

    /**
     * Get the first result or null.
     *
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        $this->limit(1);

        $results = $this->get();

        /** @var array<string, mixed>|null $first */
        $first = $results->first();

        return $first;
    }

    /**
     * Count the results (by fetching them).
     */
    public function count(): int
    {
        return $this->get()->count();
    }

    /**
     * Check if any results exist.
     */
    public function exists(): bool
    {
        return $this->first() !== null;
    }

    /**
     * Build the query parameters array.
     *
     * @return array<string, mixed>
     */
    protected function buildQueryParams(): array
    {
        $params = [];

        foreach ($this->filters as $filter) {
            $params = array_merge($params, $filter->toArray());
        }

        if ($this->limit !== null) {
            $params['limit'] = $this->limit;
        }

        if ($this->offset !== null) {
            $params['offset'] = $this->offset;
        }

        return $params;
    }

    /**
     * Get the built query string.
     */
    public function toQueryString(): string
    {
        return http_build_query($this->buildQueryParams());
    }
}
