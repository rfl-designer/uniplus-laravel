<?php

declare(strict_types=1);

namespace Uniplus\Connections;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Uniplus\Exceptions\ConnectionException;

class RemoteConnection extends Connection
{
    protected string $routingService;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(string $name, array $config)
    {
        parent::__construct($name, $config);

        /** @var string $routingService */
        $routingService = config('uniplus.routing_service') ?? '';
        $this->routingService = $routingService;
    }

    public function getBaseUrl(): string
    {
        if ($this->baseUrl !== null) {
            return $this->baseUrl;
        }

        $this->baseUrl = $this->resolveServerUrl();

        return $this->baseUrl;
    }

    /**
     * Resolve the server URL using the routing service.
     * The result is cached to avoid repeated lookups.
     */
    protected function resolveServerUrl(): string
    {
        $cacheKey = 'uniplus_server_url_'.$this->account;

        /** @var string $cachedUrl */
        $cachedUrl = Cache::remember($cacheKey, now()->addHours(24), function (): string {
            $url = $this->routingService.'/'.$this->account;

            $response = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'text/plain',
                ])
                ->get($url);

            if ($response->failed()) {
                throw new ConnectionException(
                    "Failed to resolve server URL for account '{$this->account}': ".$response->body()
                );
            }

            $serverUrl = trim($response->body());

            if ($serverUrl === '') {
                throw new ConnectionException(
                    "Empty server URL returned for account '{$this->account}'"
                );
            }

            // Ensure URL has proper format
            if (! str_starts_with($serverUrl, 'http')) {
                $serverUrl = 'https://'.$serverUrl;
            }

            return rtrim($serverUrl, '/');
        });

        return $cachedUrl;
    }

    /**
     * Clear the cached server URL.
     */
    public function clearCachedUrl(): void
    {
        $cacheKey = 'uniplus_server_url_'.$this->account;
        Cache::forget($cacheKey);
        $this->baseUrl = null;
    }
}
