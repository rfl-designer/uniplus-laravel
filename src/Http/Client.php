<?php

declare(strict_types=1);

namespace Uniplus\Http;

use Illuminate\Http\Client\ConnectionException as HttpConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Throwable;
use Uniplus\Auth\TokenManager;
use Uniplus\Contracts\ConnectionInterface;
use Uniplus\Events\RequestFailed;
use Uniplus\Events\RequestSending;
use Uniplus\Events\RequestSent;
use Uniplus\Exceptions\AuthenticationException;
use Uniplus\Exceptions\ConnectionException;
use Uniplus\Exceptions\NotFoundException;
use Uniplus\Exceptions\UniplusException;
use Uniplus\Exceptions\ValidationException;

class Client
{
    protected ConnectionInterface $connection;

    protected TokenManager $tokenManager;

    /** @var array{timeout?: int, retry?: array{times?: int, sleep?: int}} */
    protected array $config;

    protected bool $withRetry = true;

    public function __construct(ConnectionInterface $connection, TokenManager $tokenManager)
    {
        $this->connection = $connection;
        $this->tokenManager = $tokenManager;

        /** @var array{timeout?: int, retry?: array{times?: int, sleep?: int}} $httpConfig */
        $httpConfig = config('uniplus.http') ?? [];
        $this->config = $httpConfig;
    }

    /**
     * Make a GET request.
     *
     * @param  array<string, mixed>  $query
     */
    public function get(string $endpoint, array $query = []): Response
    {
        return $this->request('GET', $endpoint, ['query' => $query]);
    }

    /**
     * Make a POST request.
     *
     * @param  array<array-key, mixed>  $data
     */
    public function post(string $endpoint, array $data = []): Response
    {
        return $this->request('POST', $endpoint, ['json' => $data]);
    }

    /**
     * Make a PUT request.
     *
     * @param  array<string, mixed>  $data
     */
    public function put(string $endpoint, array $data = []): Response
    {
        return $this->request('PUT', $endpoint, ['json' => $data]);
    }

    /**
     * Make a DELETE request.
     *
     * @param  array<string, mixed>  $data
     */
    public function delete(string $endpoint, array $data = []): Response
    {
        return $this->request('DELETE', $endpoint, ['json' => $data]);
    }

    /**
     * Disable retry on authentication failure.
     */
    public function withoutRetry(): self
    {
        $clone = clone $this;
        $clone->withRetry = false;

        return $clone;
    }

    /**
     * Make an HTTP request.
     *
     * @param  array{query?: array<string, mixed>, json?: array<array-key, mixed>}  $options
     */
    protected function request(string $method, string $endpoint, array $options = []): Response
    {
        $url = $this->buildUrl($endpoint);
        $token = $this->tokenManager->getToken();
        $startTime = microtime(true);

        $this->dispatchEvent(new RequestSending(
            method: $method,
            url: $url,
            payload: $options,
        ));

        $this->logDebug("Sending {$method} request", [
            'url' => $url,
            'has_query' => isset($options['query']),
            'has_body' => isset($options['json']),
        ]);

        try {
            $pendingRequest = $this->createPendingRequest($token->getAuthorizationHeader());

            /** @var array<string, mixed> $queryData */
            $queryData = $options['query'] ?? [];

            /** @var array<array-key, mixed> $jsonData */
            $jsonData = $options['json'] ?? [];

            $httpResponse = match ($method) {
                'GET' => $pendingRequest->get($url, $queryData),
                'POST' => $pendingRequest->post($url, $jsonData),
                'PUT' => $pendingRequest->put($url, $jsonData),
                'DELETE' => $pendingRequest->delete($url, $jsonData),
                default => throw new UniplusException("Unsupported HTTP method: {$method}"),
            };

            $response = new Response($httpResponse);
            $duration = (microtime(true) - $startTime) * 1000;

            $this->logDebug('Response received', [
                'status' => $response->status(),
                'duration_ms' => round($duration, 2),
            ]);

            // Handle authentication errors with retry
            if ($response->status() === 401 && $this->withRetry) {
                $this->logDebug('Token expired, refreshing and retrying');
                $this->tokenManager->refreshToken();

                return $this->withoutRetry()->request($method, $endpoint, $options);
            }

            $this->handleErrorResponse($response);

            $this->dispatchEvent(new RequestSent(
                method: $method,
                url: $url,
                response: $response,
                durationMs: $duration,
            ));

            return $response;
        } catch (HttpConnectionException $e) {
            $this->dispatchEvent(new RequestFailed(
                method: $method,
                url: $url,
                exception: $e,
            ));

            throw ConnectionException::unreachable($url);
        }
    }

    /**
     * Create a pending request with default configuration.
     *
     * @return PendingRequest<false>
     */
    protected function createPendingRequest(string $authorizationHeader): PendingRequest
    {
        $timeout = $this->config['timeout'] ?? 30;
        $retry = $this->config['retry'] ?? ['times' => 3, 'sleep' => 100];
        $retryTimes = $retry['times'] ?? 3;
        $retrySleep = $retry['sleep'] ?? 100;

        return Http::timeout($timeout)
            ->retry($retryTimes, $retrySleep, function (Throwable $exception): bool {
                // Only retry on connection exceptions, not on API errors
                return $exception instanceof HttpConnectionException;
            })
            ->withHeaders([
                'Authorization' => $authorizationHeader,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'idusuario' => (string) $this->connection->getUserId(),
                'idfilial' => (string) $this->connection->getBranchId(),
            ]);
    }

    /**
     * Build the full URL for an endpoint.
     */
    protected function buildUrl(string $endpoint): string
    {
        $baseUrl = $this->connection->getBaseUrl();
        $endpoint = ltrim($endpoint, '/');

        return "{$baseUrl}/{$endpoint}";
    }

    /**
     * Handle error responses.
     */
    protected function handleErrorResponse(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $status = $response->status();
        $body = $response->body();

        /** @var array<string, array<string>> $errors */
        $errors = $response->json('errors') ?? [];

        match ($status) {
            401 => throw new AuthenticationException($body, $status),
            404 => throw new NotFoundException($body, $status),
            422 => throw ValidationException::withErrors($errors, $body),
            default => throw new UniplusException($body, $status),
        };
    }

    /**
     * Dispatch an event.
     */
    protected function dispatchEvent(object $event): void
    {
        event($event);
    }

    /**
     * Log debug message if logging is enabled.
     *
     * @param  array<string, mixed>  $context
     */
    protected function logDebug(string $message, array $context = []): void
    {
        /** @var array{enabled?: bool, channel?: string|null}|null $loggingConfig */
        $loggingConfig = config('uniplus.logging');

        if (! is_array($loggingConfig) || ! ($loggingConfig['enabled'] ?? false)) {
            return;
        }

        $context['connection'] = $this->connection->getName();

        $channel = $loggingConfig['channel'] ?? null;

        /** @var LoggerInterface $logger */
        $logger = $channel !== null ? Log::channel($channel) : Log::getFacadeRoot();

        $logger->debug("[Uniplus] {$message}", $context);
    }

    /**
     * Get the connection.
     */
    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * Get the token manager.
     */
    public function getTokenManager(): TokenManager
    {
        return $this->tokenManager;
    }
}
