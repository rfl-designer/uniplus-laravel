<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Uniplus\Auth\Token;
use Uniplus\Auth\TokenManager;
use Uniplus\Connections\RemoteConnection;
use Uniplus\Events\TokenRefreshed;
use Uniplus\Exceptions\AuthenticationException;

beforeEach(function () {
    Carbon::setTestNow(Carbon::create(2024, 1, 1, 12, 0, 0));

    config(['uniplus.cache.enabled' => false]);
    config(['uniplus.logging.enabled' => false]);

    $this->connection = new RemoteConnection('test', [
        'account' => 'test-account',
        'authorization_code' => base64_encode('client:secret'),
        'user_id' => 1,
        'branch_id' => 1,
        'server_url' => 'https://api.test-server.com',
    ]);
});

afterEach(function () {
    Carbon::setTestNow();
    Http::preventStrayRequests(false);
});

describe('TokenManager', function () {
    it('fetches a new token from the API', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'new-access-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'scope' => 'public-api',
            ]),
        ]);

        Event::fake([TokenRefreshed::class]);

        $tokenManager = new TokenManager($this->connection);
        $token = $tokenManager->getToken();

        expect($token)->toBeInstanceOf(Token::class)
            ->and($token->accessToken)->toBe('new-access-token')
            ->and($token->tokenType)->toBe('Bearer')
            ->and($token->expiresIn)->toBe(3600);

        Event::assertDispatched(TokenRefreshed::class, function ($event) {
            return $event->connection === 'test'
                && $event->token->accessToken === 'new-access-token';
        });
    });

    it('throws exception when token fetch fails', function () {
        Http::fake([
            '*/oauth/token' => Http::response('Invalid credentials', 401),
        ]);

        $tokenManager = new TokenManager($this->connection);

        expect(fn () => $tokenManager->getToken())
            ->toThrow(AuthenticationException::class);
    });

    it('throws exception when access_token is missing from response', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'token_type' => 'Bearer',
                // access_token is missing
            ]),
        ]);

        $tokenManager = new TokenManager($this->connection);

        expect(fn () => $tokenManager->getToken())
            ->toThrow(AuthenticationException::class, 'access_token not found');
    });

    it('caches token when caching is enabled', function () {
        config(['uniplus.cache.enabled' => true]);

        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'cached-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'scope' => 'public-api',
            ]),
        ]);

        Event::fake([TokenRefreshed::class]);

        $tokenManager = new TokenManager($this->connection);

        // First call should fetch from API
        $token1 = $tokenManager->getToken();
        expect($token1->accessToken)->toBe('cached-token');

        // Second call should return cached token (no additional HTTP request)
        $token2 = $tokenManager->getToken();
        expect($token2->accessToken)->toBe('cached-token');

        // Only one TokenRefreshed event (first fetch)
        Event::assertDispatchedTimes(TokenRefreshed::class, 1);
    });

    it('returns cached token if not expired', function () {
        config(['uniplus.cache.enabled' => true]);

        // Pre-populate cache
        Cache::put('uniplus_token_test', [
            'access_token' => 'cached-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => 'public-api',
            'expires_at' => Carbon::now()->addHour()->format('Y-m-d H:i:s'),
        ], 3600);

        Event::fake([TokenRefreshed::class]);

        $tokenManager = new TokenManager($this->connection);
        $token = $tokenManager->getToken();

        expect($token->accessToken)->toBe('cached-token');

        // No API call or event should have been made
        Event::assertNotDispatched(TokenRefreshed::class);
    });

    it('refreshes token if it expires within 120 seconds', function () {
        config(['uniplus.cache.enabled' => true]);

        // Token expires in 60 seconds (less than 120 buffer)
        Cache::put('uniplus_token_test', [
            'access_token' => 'expiring-token',
            'token_type' => 'Bearer',
            'expires_in' => 60,
            'scope' => 'public-api',
            'expires_at' => Carbon::now()->addSeconds(60)->format('Y-m-d H:i:s'),
        ], 3600);

        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'new-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'scope' => 'public-api',
            ]),
        ]);

        Event::fake([TokenRefreshed::class]);

        $tokenManager = new TokenManager($this->connection);
        $token = $tokenManager->getToken();

        expect($token->accessToken)->toBe('new-token');
        Event::assertDispatched(TokenRefreshed::class);
    });

    it('can force refresh token', function () {
        config(['uniplus.cache.enabled' => true]);

        // Pre-populate cache with valid token
        Cache::put('uniplus_token_test', [
            'access_token' => 'old-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => 'public-api',
            'expires_at' => Carbon::now()->addHour()->format('Y-m-d H:i:s'),
        ], 3600);

        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'refreshed-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'scope' => 'public-api',
            ]),
        ]);

        Event::fake([TokenRefreshed::class]);

        $tokenManager = new TokenManager($this->connection);
        $token = $tokenManager->refreshToken();

        expect($token->accessToken)->toBe('refreshed-token');
        Event::assertDispatched(TokenRefreshed::class);
    });

    it('can clear cached token', function () {
        config(['uniplus.cache.enabled' => true]);

        Cache::put('uniplus_token_test', [
            'access_token' => 'cached-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => 'public-api',
            'expires_at' => Carbon::now()->addHour()->format('Y-m-d H:i:s'),
        ], 3600);

        $tokenManager = new TokenManager($this->connection);
        $tokenManager->forgetCache();

        expect(Cache::has('uniplus_token_test'))->toBeFalse();
    });
});
