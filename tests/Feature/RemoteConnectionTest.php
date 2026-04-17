<?php

declare(strict_types=1);

use Uniplus\Connections\RemoteConnection;
use Uniplus\Exceptions\ConnectionException;

describe('RemoteConnection', function () {
    it('creates a connection with configuration', function () {
        $connection = new RemoteConnection('production', [
            'account' => 'my-account',
            'authorization_code' => base64_encode('client:secret'),
            'user_id' => 10,
            'branch_id' => 5,
            'server_url' => 'https://api.server123.uniplus.com',
        ]);

        expect($connection->getName())->toBe('production')
            ->and($connection->getAccount())->toBe('my-account')
            ->and($connection->getAuthorizationCode())->toBe(base64_encode('client:secret'))
            ->and($connection->getUserId())->toBe(10)
            ->and($connection->getBranchId())->toBe(5);
    });

    it('returns the configured server URL as base URL', function () {
        $connection = new RemoteConnection('test', [
            'account' => 'my-account',
            'authorization_code' => 'auth-code',
            'user_id' => 1,
            'branch_id' => 1,
            'server_url' => 'https://api.server123.uniplus.com',
        ]);

        expect($connection->getBaseUrl())->toBe('https://api.server123.uniplus.com');
    });

    it('trims trailing slash from configured server URL', function () {
        $connection = new RemoteConnection('test', [
            'account' => 'slash-account',
            'authorization_code' => 'auth-code',
            'user_id' => 1,
            'branch_id' => 1,
            'server_url' => 'https://api.slash.uniplus.com/',
        ]);

        expect($connection->getBaseUrl())->toBe('https://api.slash.uniplus.com');
    });

    it('throws exception when server URL is missing', function () {
        $connection = new RemoteConnection('test', [
            'account' => 'no-url-account',
            'authorization_code' => 'auth-code',
            'user_id' => 1,
            'branch_id' => 1,
        ]);

        expect(fn () => $connection->getBaseUrl())
            ->toThrow(ConnectionException::class, "Missing 'server_url'");
    });

    it('throws exception when server URL is empty', function () {
        $connection = new RemoteConnection('test', [
            'account' => 'empty-url-account',
            'authorization_code' => 'auth-code',
            'user_id' => 1,
            'branch_id' => 1,
            'server_url' => '',
        ]);

        expect(fn () => $connection->getBaseUrl())
            ->toThrow(ConnectionException::class, "Missing 'server_url'");
    });

    it('uses default values when config is incomplete', function () {
        $connection = new RemoteConnection('minimal', [
            'account' => 'minimal-account',
            'authorization_code' => 'auth-code',
            'server_url' => 'https://api.minimal.uniplus.com',
        ]);

        expect($connection->getUserId())->toBe(1)
            ->and($connection->getBranchId())->toBe(1);
    });
});
