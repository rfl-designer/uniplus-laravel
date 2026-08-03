<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Uniplus\Exceptions\UniplusException;
use Uniplus\Facades\Uniplus as UniplusFacade;
use Uniplus\Query\Builder;
use Uniplus\Resources\DocumentoFinanceiro;
use Uniplus\Resources\EntidadeOcorrencia;
use Uniplus\Resources\FichaTecnica;
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

describe('DocumentoFinanceiro Resource', function () {
    it('returns correct endpoint', function () {
        $manager = app(UniplusManager::class);
        $resource = $manager->documentoFinanceiro();

        expect($resource)->toBeInstanceOf(DocumentoFinanceiro::class)
            ->and($resource->getEndpoint())->toBe('public-api/v1/documento-financeiro');
    });

    it('lists financial documents via GET and returns the decoded collection', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/documento-financeiro*' => Http::response([
                ['idDocumento' => 1, 'descricao' => 'Documento 1'],
                ['idDocumento' => 2, 'descricao' => 'Documento 2'],
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $documentos = $manager->documentoFinanceiro()->all();

        expect($documentos)->toBeInstanceOf(Collection::class)
            ->and($documentos)->toHaveCount(2);

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && $request->url() === 'https://api.test.uniplus.com/public-api/v1/documento-financeiro';
        });
    });

    it('lists financial documents via the fluent root and via connection()', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/documento-financeiro*' => Http::response([]),
        ]);

        UniplusFacade::documentoFinanceiro()->all();
        UniplusFacade::connection('test')->documentoFinanceiro()->all();

        $requestsToEndpoint = collect(Http::recorded())
            ->filter(fn (array $pair) => str_contains($pair[0]->url(), 'public-api/v1/documento-financeiro'));

        expect($requestsToEndpoint)->toHaveCount(2)
            ->and($requestsToEndpoint->every(fn (array $pair) => $pair[0]->method() === 'GET'))->toBeTrue();
    });

    it('fetches the boleto for a document via GET', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/documento-financeiro/42/boleto' => Http::response([
                'linhaDigitavel' => '00190.00009 01234.567890 12345.678901 1 23456789012345',
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $boleto = $manager->documentoFinanceiro()->boleto('42');

        expect($boleto['linhaDigitavel'])->toBe('00190.00009 01234.567890 12345.678901 1 23456789012345');

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && $request->url() === 'https://api.test.uniplus.com/public-api/v1/documento-financeiro/42/boleto';
        });
    });

    it('can use the query builder with where/limit/offset', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->documentoFinanceiro()->where('status', 'aberto')->limit(10)->offset(5);

        expect($builder)->toBeInstanceOf(Builder::class);

        $query = $builder->toQueryString();
        expect($query)->toContain('status.eq=aberto')
            ->and($query)->toContain('limit=10')
            ->and($query)->toContain('offset=5');
    });

    it('throws on create', function () {
        $manager = app(UniplusManager::class);
        $manager->documentoFinanceiro()->create(['descricao' => 'Test']);
    })->throws(UniplusException::class, 'Create operation is not supported');

    it('throws on update', function () {
        $manager = app(UniplusManager::class);
        $manager->documentoFinanceiro()->update(['descricao' => 'Test']);
    })->throws(UniplusException::class, 'Update operation is not supported');

    it('throws on delete', function () {
        $manager = app(UniplusManager::class);
        $manager->documentoFinanceiro()->delete('1');
    })->throws(UniplusException::class, 'Delete operation is not supported');
});

describe('FichaTecnica Resource', function () {
    it('returns correct endpoint', function () {
        $manager = app(UniplusManager::class);
        $resource = $manager->fichaTecnica();

        expect($resource)->toBeInstanceOf(FichaTecnica::class)
            ->and($resource->getEndpoint())->toBe('public-api/v1/ficha-tecnica');
    });

    it('lists technical sheets via GET and returns the decoded collection', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/ficha-tecnica*' => Http::response([
                ['codigo' => 'FT001', 'descricao' => 'Ficha 1'],
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $fichas = $manager->fichaTecnica()->all();

        expect($fichas)->toBeInstanceOf(Collection::class)
            ->and($fichas)->toHaveCount(1);

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && $request->url() === 'https://api.test.uniplus.com/public-api/v1/ficha-tecnica';
        });
    });

    it('finds a technical sheet by code via GET', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/ficha-tecnica/FT001' => Http::response([
                'codigo' => 'FT001',
                'descricao' => 'Ficha 1',
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $ficha = $manager->fichaTecnica()->find('FT001');

        expect($ficha['codigo'])->toBe('FT001');

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && $request->url() === 'https://api.test.uniplus.com/public-api/v1/ficha-tecnica/FT001';
        });
    });

    it('can use the query builder with where/limit/offset', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->fichaTecnica()->where('produto', 'PROD001')->limit(20)->offset(0);

        $query = $builder->toQueryString();
        expect($query)->toContain('produto.eq=PROD001')
            ->and($query)->toContain('limit=20')
            ->and($query)->toContain('offset=0');
    });

    it('throws on create', function () {
        $manager = app(UniplusManager::class);
        $manager->fichaTecnica()->create(['descricao' => 'Test']);
    })->throws(UniplusException::class, 'Create operation is not supported');

    it('throws on update', function () {
        $manager = app(UniplusManager::class);
        $manager->fichaTecnica()->update(['descricao' => 'Test']);
    })->throws(UniplusException::class, 'Update operation is not supported');

    it('throws on delete', function () {
        $manager = app(UniplusManager::class);
        $manager->fichaTecnica()->delete('FT001');
    })->throws(UniplusException::class, 'Delete operation is not supported');
});

describe('EntidadeOcorrencia Resource', function () {
    it('returns correct endpoint', function () {
        $manager = app(UniplusManager::class);
        $resource = $manager->entidadeOcorrencias();

        expect($resource)->toBeInstanceOf(EntidadeOcorrencia::class)
            ->and($resource->getEndpoint())->toBe('public-api/v1/entidades-ocorrencias');
    });

    it('lists entity occurrences via GET and returns the decoded collection', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/entidades-ocorrencias*' => Http::response([
                ['codigo' => 'OC001', 'descricao' => 'Ocorrência 1'],
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $ocorrencias = $manager->entidadeOcorrencias()->all();

        expect($ocorrencias)->toBeInstanceOf(Collection::class)
            ->and($ocorrencias)->toHaveCount(1);

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && $request->url() === 'https://api.test.uniplus.com/public-api/v1/entidades-ocorrencias';
        });
    });

    it('finds an entity occurrence by code via GET', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/entidades-ocorrencias/OC001' => Http::response([
                'codigo' => 'OC001',
                'descricao' => 'Ocorrência 1',
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $ocorrencia = $manager->entidadeOcorrencias()->find('OC001');

        expect($ocorrencia['codigo'])->toBe('OC001');

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && $request->url() === 'https://api.test.uniplus.com/public-api/v1/entidades-ocorrencias/OC001';
        });
    });

    it('can use the query builder with where/limit/offset', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->entidadeOcorrencias()->where('entidade', 'ENT001')->limit(15)->offset(3);

        $query = $builder->toQueryString();
        expect($query)->toContain('entidade.eq=ENT001')
            ->and($query)->toContain('limit=15')
            ->and($query)->toContain('offset=3');
    });

    it('throws on create', function () {
        $manager = app(UniplusManager::class);
        $manager->entidadeOcorrencias()->create(['descricao' => 'Test']);
    })->throws(UniplusException::class, 'Create operation is not supported');

    it('throws on update', function () {
        $manager = app(UniplusManager::class);
        $manager->entidadeOcorrencias()->update(['descricao' => 'Test']);
    })->throws(UniplusException::class, 'Update operation is not supported');

    it('throws on delete', function () {
        $manager = app(UniplusManager::class);
        $manager->entidadeOcorrencias()->delete('OC001');
    })->throws(UniplusException::class, 'Delete operation is not supported');
});

describe('Facade and root accessors', function () {
    it('exposes documentoFinanceiro on the root class and facade', function () {
        $manager = app(UniplusManager::class);

        expect($manager->connection('test')->documentoFinanceiro())->toBeInstanceOf(DocumentoFinanceiro::class)
            ->and(UniplusFacade::documentoFinanceiro())->toBeInstanceOf(DocumentoFinanceiro::class);
    });

    it('exposes fichaTecnica on the root class and facade', function () {
        $manager = app(UniplusManager::class);

        expect($manager->connection('test')->fichaTecnica())->toBeInstanceOf(FichaTecnica::class)
            ->and(UniplusFacade::fichaTecnica())->toBeInstanceOf(FichaTecnica::class);
    });

    it('exposes entidadeOcorrencias on the root class and facade', function () {
        $manager = app(UniplusManager::class);

        expect($manager->connection('test')->entidadeOcorrencias())->toBeInstanceOf(EntidadeOcorrencia::class)
            ->and(UniplusFacade::entidadeOcorrencias())->toBeInstanceOf(EntidadeOcorrencia::class);
    });
});
