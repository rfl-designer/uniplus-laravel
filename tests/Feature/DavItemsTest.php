<?php

declare(strict_types=1);

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Uniplus\Resources\Dav;
use Uniplus\UniplusManager;

beforeEach(function () {
    config([
        'uniplus.default' => 'test',
        'uniplus.connections.test' => [
            'account' => 'test-account',
            'authorization_code' => base64_encode('client:secret'),
            'user_id' => 1,
            'branch_id' => 1,
            'server_url' => 'https://api.test.uniplus.com',
        ],
        'uniplus.cache.enabled' => false,
        'uniplus.logging.enabled' => false,
    ]);

    Http::fake([
        '*/oauth/token' => Http::response([
            'access_token' => 'test-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]),
    ]);
});

afterEach(function () {
    Http::preventStrayRequests(false);
});

describe('Dav Resource - Items', function () {
    it('can list items of a DAV via GET on the nested item path', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/davs/DAV001/item/1*' => Http::response([
                ['codigo' => 'PROD001', 'quantidade' => 2],
                ['codigo' => 'PROD002', 'quantidade' => 1],
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $itens = $manager->davs()->items('DAV001', '1');

        expect($itens)->toBeInstanceOf(Collection::class)
            ->and($itens)->toHaveCount(2);

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && $request->url() === 'https://api.test.uniplus.com/public-api/v1/davs/DAV001/item/1';
        });
    });

    it('can list items of a DAV with offset/limit pagination', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/davs/DAV001/item/1*' => Http::response([
                ['codigo' => 'PROD002', 'quantidade' => 1],
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $itens = $manager->davs()->items('DAV001', '1', ['offset' => 10, 'limit' => 5]);

        expect($itens)->toHaveCount(1);

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && str_starts_with($request->url(), 'https://api.test.uniplus.com/public-api/v1/davs/DAV001/item/1')
                && str_contains($request->url(), 'offset=10')
                && str_contains($request->url(), 'limit=5');
        });
    });

    it('can get an item by código via GET on the three-segment path', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/davs/DAV001/item/1/PROD001' => Http::response([
                'codigo' => 'PROD001',
                'quantidade' => 2,
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $item = $manager->davs()->findItem('DAV001', '1', 'PROD001');

        expect($item['codigo'])->toBe('PROD001')
            ->and($item['quantidade'])->toBe(2);

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && $request->url() === 'https://api.test.uniplus.com/public-api/v1/davs/DAV001/item/1/PROD001';
        });
    });

    it('can update the quantity of an item via PUT with the exact payload', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/davs/DAV001/item/1/quantidade/4' => Http::response([
                'codigo' => 'PROD001',
                'quantidade' => 5,
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $result = $manager->davs()->updateItemQuantity('DAV001', '1', Dav::TIPO_PEDIDO_VENDA, [
            'quantidade' => 5,
        ]);

        expect($result['quantidade'])->toBe(5);

        Http::assertSent(function ($request) {
            return $request->method() === 'PUT'
                && $request->url() === 'https://api.test.uniplus.com/public-api/v1/davs/DAV001/item/1/quantidade/4'
                && $request->data() === ['quantidade' => 5];
        });
    });

    it('can delete an item via DELETE on the same quantidade path', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/davs/DAV001/item/1/quantidade/4' => Http::response(null, 204),
        ]);

        $manager = app(UniplusManager::class);
        $result = $manager->davs()->deleteItem('DAV001', '1', Dav::TIPO_PEDIDO_VENDA);

        expect($result)->toBeTrue();

        Http::assertSent(function ($request) {
            return $request->method() === 'DELETE'
                && $request->url() === 'https://api.test.uniplus.com/public-api/v1/davs/DAV001/item/1/quantidade/4'
                && $request->data() === [];
        });
    });

    // NOTE: Client::handleErrorResponse() maps 401/422 to AuthenticationException/ValidationException,
    // but that code is currently unreachable for ANY resource: the underlying PendingRequest::retry()
    // call in Client::createPendingRequest() defaults `throw` to true, so Laravel's HTTP client already
    // throws Illuminate\Http\Client\RequestException before Client::request() gets a chance to inspect
    // the response. Reproduced against an existing resource (Produto::find) to confirm this is a
    // pre-existing, package-wide gap and not specific to these new Dav item methods — see report.
    it('propagates a request exception carrying the original 401 status when the server rejects the request', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/davs/DAV001/item/1/PROD001' => Http::response('Unauthorized', 401),
        ]);

        $manager = app(UniplusManager::class);

        try {
            $manager->davs()->findItem('DAV001', '1', 'PROD001');
            expect(false)->toBeTrue('Expected a RequestException to be thrown.');
        } catch (RequestException $exception) {
            expect($exception->response->status())->toBe(401);
        }
    });

    it('propagates a request exception carrying the original 422 status when the server rejects the request', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/davs/DAV001/item/1/quantidade/4' => Http::response([
                'errors' => ['quantidade' => ['must be greater than zero']],
            ], 422),
        ]);

        $manager = app(UniplusManager::class);

        try {
            $manager->davs()->updateItemQuantity('DAV001', '1', Dav::TIPO_PEDIDO_VENDA, [
                'quantidade' => 0,
            ]);
            expect(false)->toBeTrue('Expected a RequestException to be thrown.');
        } catch (RequestException $exception) {
            expect($exception->response->status())->toBe(422);
        }
    });
});
