<?php

declare(strict_types=1);

namespace Uniplus\Auth;

use DateTimeInterface;
use Illuminate\Support\Carbon;

class Token
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $tokenType,
        public readonly int $expiresIn,
        public readonly string $scope,
        public readonly DateTimeInterface $expiresAt,
    ) {}

    /**
     * Create a Token from API response data.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): self
    {
        $expiresInValue = $data['expires_in'] ?? 3600;
        $expiresIn = is_int($expiresInValue) ? $expiresInValue : (int) (is_numeric($expiresInValue) ? $expiresInValue : 3600);

        /** @var string $accessToken */
        $accessToken = $data['access_token'] ?? '';

        /** @var string $tokenType */
        $tokenType = $data['token_type'] ?? 'Bearer';

        /** @var string $scope */
        $scope = $data['scope'] ?? 'public-api';

        return new self(
            accessToken: $accessToken,
            tokenType: $tokenType,
            expiresIn: $expiresIn,
            scope: $scope,
            expiresAt: Carbon::now()->addSeconds($expiresIn),
        );
    }

    /**
     * Check if the token has expired.
     */
    public function isExpired(): bool
    {
        return Carbon::now()->isAfter($this->expiresAt);
    }

    /**
     * Check if the token will expire within the given seconds.
     */
    public function expiresWithin(int $seconds): bool
    {
        return Carbon::now()->addSeconds($seconds)->isAfter($this->expiresAt);
    }

    /**
     * Get the Authorization header value.
     */
    public function getAuthorizationHeader(): string
    {
        return "{$this->tokenType} {$this->accessToken}";
    }

    /**
     * Convert to array for caching.
     *
     * @return array<string, string|int>
     */
    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'token_type' => $this->tokenType,
            'expires_in' => $this->expiresIn,
            'scope' => $this->scope,
            'expires_at' => $this->expiresAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Create a Token from cached array data.
     *
     * @param  array<string, string|int>  $data
     */
    public static function fromCache(array $data): self
    {
        /** @var string $accessToken */
        $accessToken = $data['access_token'];

        /** @var string $tokenType */
        $tokenType = $data['token_type'];

        $expiresInValue = $data['expires_in'];
        $expiresIn = is_int($expiresInValue) ? $expiresInValue : (int) (is_numeric($expiresInValue) ? $expiresInValue : 0);

        /** @var string $scope */
        $scope = $data['scope'];

        /** @var string $expiresAt */
        $expiresAt = $data['expires_at'];

        return new self(
            accessToken: $accessToken,
            tokenType: $tokenType,
            expiresIn: $expiresIn,
            scope: $scope,
            expiresAt: Carbon::parse($expiresAt),
        );
    }
}
