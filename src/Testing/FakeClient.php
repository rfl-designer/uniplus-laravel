<?php

declare(strict_types=1);

namespace Uniplus\Testing;

use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert;

class FakeClient
{
    /** @var array<string, array<string, mixed>> */
    protected array $responses;

    /** @var array<int, array{method: string, url: string, payload: array<string, mixed>}> */
    protected array $recorded = [];

    /**
     * @param  array<string, array<string, mixed>>  $responses
     */
    public function __construct(array $responses = [])
    {
        $this->responses = $responses;
    }

    /**
     * Get fake response for a given endpoint.
     *
     * @return array<string, mixed>|null
     */
    public function getResponse(string $endpoint): ?array
    {
        // Try exact match first
        if (isset($this->responses[$endpoint])) {
            /** @var array<string, mixed> */
            return $this->responses[$endpoint];
        }

        // Try partial match
        foreach ($this->responses as $pattern => $response) {
            if (str_contains($endpoint, $pattern)) {
                /** @var array<string, mixed> */
                return $response;
            }
        }

        return null;
    }

    /**
     * Record a request.
     *
     * @param  array<string, mixed>  $payload
     */
    public function record(string $method, string $url, array $payload = []): void
    {
        $this->recorded[] = [
            'method' => $method,
            'url' => $url,
            'payload' => $payload,
        ];
    }

    /**
     * Get all recorded requests.
     *
     * @return array<int, array{method: string, url: string, payload: array<string, mixed>}>
     */
    public function recorded(): array
    {
        return $this->recorded;
    }

    /**
     * Assert that a request was sent.
     */
    public function assertSent(string $method, string $url): void
    {
        $found = collect($this->recorded)->contains(function ($request) use ($method, $url) {
            return $request['method'] === $method && str_contains($request['url'], $url);
        });

        Assert::assertTrue(
            $found,
            "Expected [{$method}] request to [{$url}] was not sent."
        );
    }

    /**
     * Assert that a request was not sent.
     */
    public function assertNotSent(string $url): void
    {
        $found = collect($this->recorded)->contains(function ($request) use ($url) {
            return str_contains($request['url'], $url);
        });

        Assert::assertFalse(
            $found,
            "Unexpected request to [{$url}] was sent."
        );
    }

    /**
     * Assert the number of requests sent.
     */
    public function assertSentCount(int $count): void
    {
        $actualCount = count($this->recorded);

        Assert::assertEquals(
            $count,
            $actualCount,
            "Expected {$count} requests to be sent, but {$actualCount} were sent."
        );
    }

    /**
     * Assert that no requests were sent.
     */
    public function assertNothingSent(): void
    {
        $this->assertSentCount(0);
    }

    /**
     * Get recorded requests as a collection.
     *
     * @return Collection<int, array{method: string, url: string, payload: array<string, mixed>}>
     */
    public function getRecorded(): Collection
    {
        return collect($this->recorded);
    }

    /**
     * Clear all recorded requests.
     */
    public function clear(): void
    {
        $this->recorded = [];
    }

    /**
     * Add or update a response.
     *
     * @param  array<string, mixed>  $response
     */
    public function addResponse(string $endpoint, array $response): self
    {
        $this->responses[$endpoint] = $response;

        return $this;
    }
}
