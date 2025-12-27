<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Uniplus\Connections\RemoteConnection;
use Uniplus\Exceptions\ConnectionException;

beforeEach(function () {
    Cache::flush();

    // Set up routing service URL in config
    config(['uniplus.routing_service' => 'https://test-router.example.com']);
});

afterEach(function () {
    Http::preventStrayRequests(false);
});

describe('RemoteConnection', function () {
    it('creates a connection with configuration', function () {
        $connection = new RemoteConnection('production', [
            'account' => 'my-account',
            'authorization_code' => base64_encode('client:secret'),
            'user_id' => 10,
            'branch_id' => 5,
        ]);

        expect($connection->getName())->toBe('production')
            ->and($connection->getAccount())->toBe('my-account')
            ->and($connection->getAuthorizationCode())->toBe(base64_encode('client:secret'))
            ->and($connection->getUserId())->toBe(10)
            ->and($connection->getBranchId())->toBe(5);
    });

    it('resolves base URL from routing service', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.server123.uniplus.com'),
        ]);

        $connection = new RemoteConnection('test', [
            'account' => 'my-account',
            'authorization_code' => 'auth-code',
            'user_id' => 1,
            'branch_id' => 1,
        ]);

        $baseUrl = $connection->getBaseUrl();

        expect($baseUrl)->toBe('https://api.server123.uniplus.com');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'my-account');
        });
    });

    it('caches the resolved base URL', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.cached.uniplus.com'),
        ]);

        $connection = new RemoteConnection('test', [
            'account' => 'cached-account',
            'authorization_code' => 'auth-code',
            'user_id' => 1,
            'branch_id' => 1,
        ]);

        // First call
        $url1 = $connection->getBaseUrl();

        // Second call should use cache
        $url2 = $connection->getBaseUrl();

        expect($url1)->toBe('https://api.cached.uniplus.com')
            ->and($url2)->toBe($url1);

        // Only one HTTP request should have been made
        Http::assertSentCount(1);
    });

    it('adds https prefix if missing from routing response', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('api.noprotocol.uniplus.com'),
        ]);

        $connection = new RemoteConnection('test', [
            'account' => 'noprotocol-account',
            'authorization_code' => 'auth-code',
            'user_id' => 1,
            'branch_id' => 1,
        ]);

        $baseUrl = $connection->getBaseUrl();

        expect($baseUrl)->toBe('https://api.noprotocol.uniplus.com');
    });

    it('trims trailing slash from base URL', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.slash.uniplus.com/'),
        ]);

        $connection = new RemoteConnection('test', [
            'account' => 'slash-account',
            'authorization_code' => 'auth-code',
            'user_id' => 1,
            'branch_id' => 1,
        ]);

        $baseUrl = $connection->getBaseUrl();

        expect($baseUrl)->toBe('https://api.slash.uniplus.com');
    });

    it('throws exception when routing service fails', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('Server error', 500),
        ]);

        $connection = new RemoteConnection('test', [
            'account' => 'error-account',
            'authorization_code' => 'auth-code',
            'user_id' => 1,
            'branch_id' => 1,
        ]);

        expect(fn () => $connection->getBaseUrl())
            ->toThrow(ConnectionException::class, 'Failed to resolve server URL');
    });

    it('throws exception when routing service returns empty response', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response(''),
        ]);

        $connection = new RemoteConnection('test', [
            'account' => 'empty-account',
            'authorization_code' => 'auth-code',
            'user_id' => 1,
            'branch_id' => 1,
        ]);

        expect(fn () => $connection->getBaseUrl())
            ->toThrow(ConnectionException::class, 'Empty server URL');
    });

    it('can clear cached URL', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::sequence()
                ->push('https://old-server.uniplus.com')
                ->push('https://new-server.uniplus.com'),
        ]);

        $connection = new RemoteConnection('test', [
            'account' => 'clear-cache-account',
            'authorization_code' => 'auth-code',
            'user_id' => 1,
            'branch_id' => 1,
        ]);

        // First call
        $url1 = $connection->getBaseUrl();
        expect($url1)->toBe('https://old-server.uniplus.com');

        // Clear cache
        $connection->clearCachedUrl();

        // Second call should get new URL
        $url2 = $connection->getBaseUrl();
        expect($url2)->toBe('https://new-server.uniplus.com');
    });

    it('uses default values when config is incomplete', function () {
        $connection = new RemoteConnection('minimal', [
            'account' => 'minimal-account',
            'authorization_code' => 'auth-code',
        ]);

        expect($connection->getUserId())->toBe(1)
            ->and($connection->getBranchId())->toBe(1);
    });
});
