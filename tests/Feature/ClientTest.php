<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Uniplus\Auth\TokenManager;
use Uniplus\Connections\RemoteConnection;
use Uniplus\Events\RequestFailed;
use Uniplus\Events\RequestSending;
use Uniplus\Events\RequestSent;
use Uniplus\Exceptions\ConnectionException;
use Uniplus\Http\Client;
use Uniplus\Http\Response;

beforeEach(function () {
    config(['uniplus.cache.enabled' => false]);
    config(['uniplus.logging.enabled' => false]);

    $this->connection = new RemoteConnection('test', [
        'account' => 'test-account',
        'authorization_code' => base64_encode('client:secret'),
        'user_id' => 1,
        'branch_id' => 1,
        'server_url' => 'https://api.test.uniplus.com',
    ]);
});

afterEach(function () {
    Http::preventStrayRequests(false);
});

describe('Client HTTP Methods', function () {
    it('makes GET requests', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/produtos*' => Http::response([
                ['id' => 1, 'name' => 'Product 1'],
                ['id' => 2, 'name' => 'Product 2'],
            ]),
        ]);

        $tokenManager = new TokenManager($this->connection);
        $client = new Client($this->connection, $tokenManager);

        $response = $client->get('public-api/v1/produtos', ['limit' => 10]);

        expect($response)->toBeInstanceOf(Response::class)
            ->and($response->json())->toHaveCount(2);

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && str_contains($request->url(), 'produtos')
                && $request->hasHeader('Authorization');
        });
    });

    it('makes POST requests', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/produtos' => Http::response([
                'id' => 1,
                'name' => 'New Product',
            ], 201),
        ]);

        $tokenManager = new TokenManager($this->connection);
        $client = new Client($this->connection, $tokenManager);

        $response = $client->post('public-api/v1/produtos', [
            'name' => 'New Product',
            'price' => 99.99,
        ]);

        expect($response)->toBeInstanceOf(Response::class)
            ->and($response->json('id'))->toBe(1);

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && str_contains($request->url(), 'produtos');
        });
    });

    it('makes PUT requests', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/produtos' => Http::response([
                'id' => 1,
                'name' => 'Updated Product',
            ]),
        ]);

        $tokenManager = new TokenManager($this->connection);
        $client = new Client($this->connection, $tokenManager);

        $response = $client->put('public-api/v1/produtos', [
            'id' => 1,
            'name' => 'Updated Product',
        ]);

        expect($response->json('name'))->toBe('Updated Product');

        Http::assertSent(function ($request) {
            return $request->method() === 'PUT';
        });
    });

    it('makes DELETE requests', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/produtos' => Http::response(null, 204),
        ]);

        $tokenManager = new TokenManager($this->connection);
        $client = new Client($this->connection, $tokenManager);

        $response = $client->delete('public-api/v1/produtos', ['codigo' => '123']);

        expect($response->successful())->toBeTrue();

        Http::assertSent(function ($request) {
            return $request->method() === 'DELETE';
        });
    });

    it('includes required headers', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*' => Http::response([]),
        ]);

        $tokenManager = new TokenManager($this->connection);
        $client = new Client($this->connection, $tokenManager);

        $client->get('public-api/v1/test');

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test-token')
                && $request->hasHeader('Content-Type', 'application/json')
                && $request->hasHeader('Accept', 'application/json')
                && $request->hasHeader('idusuario', '1')
                && $request->hasHeader('idfilial', '1');
        });
    });
});

describe('Client Events', function () {
    it('dispatches RequestSending event before request', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*' => Http::response([]),
        ]);

        Event::fake([RequestSending::class, RequestSent::class]);

        $tokenManager = new TokenManager($this->connection);
        $client = new Client($this->connection, $tokenManager);

        $client->get('public-api/v1/test');

        Event::assertDispatched(RequestSending::class, function ($event) {
            return $event->method === 'GET'
                && str_contains($event->url, 'test');
        });
    });

    it('dispatches RequestSent event after successful request', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*' => Http::response(['success' => true]),
        ]);

        Event::fake([RequestSending::class, RequestSent::class]);

        $tokenManager = new TokenManager($this->connection);
        $client = new Client($this->connection, $tokenManager);

        $client->get('public-api/v1/test');

        Event::assertDispatched(RequestSent::class, function ($event) {
            return $event->method === 'GET'
                && $event->response instanceof Response
                && $event->durationMs > 0;
        });
    });

    it('dispatches RequestFailed event on connection error', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection failed');
            },
        ]);

        Event::fake([RequestFailed::class]);

        $tokenManager = new TokenManager($this->connection);
        $client = new Client($this->connection, $tokenManager);

        try {
            $client->get('public-api/v1/test');
        } catch (ConnectionException) {
            // Expected
        }

        Event::assertDispatched(RequestFailed::class);
    });
});

describe('Client Error Handling', function () {
    it('throws ConnectionException on network error', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection failed');
            },
        ]);

        $tokenManager = new TokenManager($this->connection);
        $client = new Client($this->connection, $tokenManager);

        expect(fn () => $client->get('public-api/v1/test'))
            ->toThrow(ConnectionException::class);
    });
});

describe('Client Configuration', function () {
    it('can disable retry', function () {
        $tokenManager = new TokenManager($this->connection);
        $client = new Client($this->connection, $tokenManager);

        $clientWithoutRetry = $client->withoutRetry();

        expect($clientWithoutRetry)->not->toBe($client)
            ->and($clientWithoutRetry)->toBeInstanceOf(Client::class);
    });

    it('returns connection', function () {
        $tokenManager = new TokenManager($this->connection);
        $client = new Client($this->connection, $tokenManager);

        expect($client->getConnection())->toBe($this->connection);
    });

    it('returns token manager', function () {
        $tokenManager = new TokenManager($this->connection);
        $client = new Client($this->connection, $tokenManager);

        expect($client->getTokenManager())->toBe($tokenManager);
    });
});
