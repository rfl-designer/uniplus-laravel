<?php

declare(strict_types=1);

namespace Uniplus\Auth;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Uniplus\Contracts\ConnectionInterface;
use Uniplus\Events\TokenRefreshed;
use Uniplus\Exceptions\AuthenticationException;

class TokenManager
{
    protected ConnectionInterface $connection;

    /** @var array{enabled?: bool, store?: string|null, prefix?: string, ttl?: int} */
    protected array $config;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;

        /** @var array{enabled?: bool, store?: string|null, prefix?: string, ttl?: int} $cacheConfig */
        $cacheConfig = config('uniplus.cache') ?? [];
        $this->config = $cacheConfig;
    }

    /**
     * Get a valid token for the connection.
     */
    public function getToken(): Token
    {
        if ($this->isCacheEnabled()) {
            $cached = $this->getFromCache();

            if ($cached !== null && ! $cached->isExpired() && ! $cached->expiresWithin(120)) {
                return $cached;
            }
        }

        $token = $this->fetchNewToken();

        if ($this->isCacheEnabled()) {
            $this->storeInCache($token);
        }

        event(new TokenRefreshed(
            connection: $this->connection->getName(),
            token: $token,
        ));

        return $token;
    }

    /**
     * Force refresh the token.
     */
    public function refreshToken(): Token
    {
        $this->forgetCache();

        return $this->getToken();
    }

    /**
     * Fetch a new token from the API.
     */
    protected function fetchNewToken(): Token
    {
        $baseUrl = $this->connection->getBaseUrl();
        $authCode = $this->connection->getAuthorizationCode();

        $this->logDebug('Fetching new token', [
            'url' => $baseUrl.'/oauth/token',
            'connection' => $this->connection->getName(),
        ]);

        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Basic '.$authCode,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])
            ->asForm()
            ->post($baseUrl.'/oauth/token', [
                'grant_type' => 'client_credentials',
                'scope' => 'public-api',
            ]);

        if ($response->failed()) {
            $this->logDebug('Token fetch failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new AuthenticationException(
                'Failed to obtain access token: '.$response->body(),
                $response->status(),
            );
        }

        /** @var array<string, mixed>|null $data */
        $data = $response->json();

        if (! is_array($data) || ! isset($data['access_token'])) {
            throw new AuthenticationException(
                'Invalid token response: access_token not found',
            );
        }

        $this->logDebug('Token obtained successfully', [
            'expires_in' => $data['expires_in'] ?? 'unknown',
        ]);

        return Token::fromResponse($data);
    }

    /**
     * Get token from cache.
     */
    protected function getFromCache(): ?Token
    {
        $key = $this->getCacheKey();

        /** @var array<string, string|int>|null $data */
        $data = $this->getCache()->get($key);

        if ($data === null) {
            return null;
        }

        return Token::fromCache($data);
    }

    /**
     * Store token in cache.
     */
    protected function storeInCache(Token $token): void
    {
        $key = $this->getCacheKey();
        $ttl = $this->config['ttl'] ?? 3500;

        $this->getCache()->put($key, $token->toArray(), $ttl);
    }

    /**
     * Clear the cached token.
     */
    public function forgetCache(): void
    {
        $key = $this->getCacheKey();
        $this->getCache()->forget($key);
    }

    /**
     * Get the cache key for this connection.
     */
    protected function getCacheKey(): string
    {
        $prefix = $this->config['prefix'] ?? 'uniplus_token_';

        return $prefix.$this->connection->getName();
    }

    /**
     * Check if caching is enabled.
     */
    protected function isCacheEnabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? true);
    }

    /**
     * Get the cache repository.
     */
    protected function getCache(): CacheRepository
    {
        $store = $this->config['store'] ?? null;

        return Cache::store($store);
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

        $channel = $loggingConfig['channel'] ?? null;

        /** @var LoggerInterface $logger */
        $logger = $channel !== null ? Log::channel($channel) : Log::getFacadeRoot();

        $logger->debug("[Uniplus] {$message}", $context);
    }
}
