<?php

declare(strict_types=1);

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Uniplus\Events\RequestFailed;
use Uniplus\Events\RequestSending;
use Uniplus\Events\RequestSent;
use Uniplus\Exceptions\ConnectionException;
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

describe('Produto Resource - Image Upload', function () {
    it('emits a multipart POST to the images route with binary "file" and JSON "entity" parts', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/produtos/imagens' => Http::response([
                'codigo' => 'IMG001',
            ], 201),
        ]);

        $manager = app(UniplusManager::class);
        $result = $manager->produtos()->uploadImagem('PROD001', 1, 'Foto principal', 'binary-image-content', 'produto.jpg');

        expect($result['codigo'])->toBe('IMG001');

        Http::assertSent(function ($request) {
            if ($request->method() !== 'POST'
                || $request->url() !== 'https://api.test.uniplus.com/public-api/v1/produtos/imagens'
                || ! $request->isMultipart()) {
                return false;
            }

            if (! $request->hasFile('file', 'binary-image-content', 'produto.jpg')) {
                return false;
            }

            $entityPart = collect($request->data())->firstWhere('name', 'entity');

            return $entityPart !== null
                && json_decode((string) $entityPart['contents'], true) === [
                    'codigoProduto' => 'PROD001',
                    'tipoImagem' => 1,
                    'descricaoImagem' => 'Foto principal',
                ];
        });
    });

    it('carries the Bearer token and connection headers like any other call', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/produtos/imagens' => Http::response(['codigo' => 'IMG001'], 201),
        ]);

        $manager = app(UniplusManager::class);
        $manager->produtos()->uploadImagem('PROD001', 1, 'Foto principal', 'binary-image-content', 'produto.jpg');

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test-token')
                && $request->hasHeader('idusuario', '1')
                && $request->hasHeader('idfilial', '1');
        });
    });

    it('dispatches RequestSending and RequestSent events for a successful upload', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/produtos/imagens' => Http::response(['codigo' => 'IMG001'], 201),
        ]);

        Event::fake([RequestSending::class, RequestSent::class]);

        $manager = app(UniplusManager::class);
        $manager->produtos()->uploadImagem('PROD001', 1, 'Foto principal', 'binary-image-content', 'produto.jpg');

        Event::assertDispatched(RequestSending::class, function ($event) {
            return $event->method === 'POST' && str_contains($event->url, 'produtos/imagens');
        });

        Event::assertDispatched(RequestSent::class, function ($event) {
            return $event->method === 'POST';
        });
    });

    // NOTE: Client::handleErrorResponse() maps 401/422 to AuthenticationException/ValidationException,
    // but that code is currently unreachable for ANY resource (see DavItemsTest.php / PR #18): the
    // underlying PendingRequest::retry() call in Client::createPendingRequest() defaults `throw` to
    // true, so Laravel's HTTP client already throws Illuminate\Http\Client\RequestException before
    // Client::request() gets a chance to inspect the response. The multipart upload reuses the exact
    // same createPendingRequest()/request() pipeline, so it is affected identically — the 401
    // refresh+retry branch never runs. Documenting the real behavior per the mini-PRD's cicatriz note,
    // not the nominally expected typed exception.
    it('propagates a request exception carrying the original 401 status instead of retrying (cicatriz do Client)', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/produtos/imagens' => Http::response('Unauthorized', 401),
        ]);

        $manager = app(UniplusManager::class);

        try {
            $manager->produtos()->uploadImagem('PROD001', 1, 'Foto principal', 'binary-image-content', 'produto.jpg');
            expect(false)->toBeTrue('Expected a RequestException to be thrown.');
        } catch (RequestException $exception) {
            expect($exception->response->status())->toBe(401);
        }
    });

    it('propagates a request exception carrying the original 422 status (cicatriz do Client)', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/produtos/imagens' => Http::response([
                'errors' => ['tipoImagem' => ['invalid value']],
            ], 422),
        ]);

        $manager = app(UniplusManager::class);

        try {
            $manager->produtos()->uploadImagem('PROD001', 99, 'Foto principal', 'binary-image-content', 'produto.jpg');
            expect(false)->toBeTrue('Expected a RequestException to be thrown.');
        } catch (RequestException $exception) {
            expect($exception->response->status())->toBe(422);
        }
    });

    it('does not dispatch RequestFailed for a 401 upload response (only ConnectionException triggers it)', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/produtos/imagens' => Http::response('Unauthorized', 401),
        ]);

        Event::fake([RequestFailed::class, RequestSent::class]);

        $manager = app(UniplusManager::class);

        try {
            $manager->produtos()->uploadImagem('PROD001', 1, 'Foto principal', 'binary-image-content', 'produto.jpg');
        } catch (RequestException) {
            // Expected — see cicatriz test above.
        }

        Event::assertNotDispatched(RequestFailed::class);
        Event::assertNotDispatched(RequestSent::class);
    });

    it('dispatches RequestSending and RequestFailed (but not RequestSent) and propagates a ConnectionException on a connection failure during upload', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/produtos/imagens' => function () {
                throw new Illuminate\Http\Client\ConnectionException('Connection failed');
            },
        ]);

        Event::fake([RequestSending::class, RequestFailed::class, RequestSent::class]);

        $manager = app(UniplusManager::class);

        try {
            $manager->produtos()->uploadImagem('PROD001', 1, 'Foto principal', 'binary-image-content', 'produto.jpg');
            expect(false)->toBeTrue('Expected a ConnectionException to be thrown.');
        } catch (ConnectionException $exception) {
            expect($exception)->toBeInstanceOf(ConnectionException::class);
        }

        Event::assertDispatched(RequestSending::class, function ($event) {
            return $event->method === 'POST' && str_contains($event->url, 'produtos/imagens');
        });

        Event::assertDispatched(RequestFailed::class, function ($event) {
            return $event->method === 'POST';
        });

        Event::assertNotDispatched(RequestSent::class);
    });
});
