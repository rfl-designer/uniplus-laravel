<?php

declare(strict_types=1);

namespace Uniplus\Http;

use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Collection;

class Response
{
    public function __construct(
        protected HttpResponse $response,
    ) {}

    /**
     * Get the response status code.
     */
    public function status(): int
    {
        return $this->response->status();
    }

    /**
     * Check if the request was successful.
     */
    public function successful(): bool
    {
        return $this->response->successful();
    }

    /**
     * Check if the request failed.
     */
    public function failed(): bool
    {
        return $this->response->failed();
    }

    /**
     * Get the response body as string.
     */
    public function body(): string
    {
        return $this->response->body();
    }

    /**
     * Get the response as JSON array.
     *
     * @return array<int|string, mixed>|mixed
     */
    public function json(?string $key = null): mixed
    {
        return $this->response->json($key);
    }

    /**
     * Get the response as a collection.
     *
     * @return Collection<int|string, mixed>
     */
    public function collect(): Collection
    {
        /** @var array<int|string, mixed> $data */
        $data = $this->response->json() ?? [];

        return collect($data);
    }

    /**
     * Get a header from the response.
     */
    public function header(string $header): ?string
    {
        return $this->response->header($header);
    }

    /**
     * Get all headers from the response.
     *
     * @return array<string, array<int, string>>
     */
    public function headers(): array
    {
        return $this->response->headers();
    }

    /**
     * Check if response is a client error (4xx).
     */
    public function clientError(): bool
    {
        return $this->response->clientError();
    }

    /**
     * Check if response is a server error (5xx).
     */
    public function serverError(): bool
    {
        return $this->response->serverError();
    }

    /**
     * Get the underlying HTTP response.
     */
    public function toHttpResponse(): HttpResponse
    {
        return $this->response;
    }
}
