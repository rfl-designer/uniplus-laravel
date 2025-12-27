<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Uniplus\Facades\Uniplus;
use Uniplus\Resources\Dav;
use Uniplus\Resources\Entidade;
use Uniplus\Resources\Produto;
use Uniplus\Resources\SaldoEstoque;
use Uniplus\Resources\Venda;
use Uniplus\Resources\VendaItem;
use Uniplus\Uniplus as UniplusClient;

beforeEach(function () {
    config([
        'uniplus.default' => 'test',
        'uniplus.connections.test' => [
            'account' => 'test-account',
            'authorization_code' => base64_encode('client:secret'),
            'user_id' => 1,
            'branch_id' => 1,
        ],
        'uniplus.cache.enabled' => false,
        'uniplus.logging.enabled' => false,
    ]);

    Http::fake([
        'https://uniplus.info/service-locator/*' => Http::response('https://api.test.uniplus.com'),
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

describe('Uniplus Facade', function () {
    it('resolves the correct facade accessor', function () {
        expect(Uniplus::getFacadeRoot())->toBeInstanceOf(\Uniplus\UniplusManager::class);
    });

    it('provides connection method', function () {
        $client = Uniplus::connection('test');

        expect($client)->toBeInstanceOf(UniplusClient::class);
    });

    it('provides produtos method', function () {
        $produtos = Uniplus::produtos();

        expect($produtos)->toBeInstanceOf(Produto::class);
    });

    it('provides entidades method', function () {
        $entidades = Uniplus::entidades();

        expect($entidades)->toBeInstanceOf(Entidade::class);
    });

    it('provides davs method', function () {
        $davs = Uniplus::davs();

        expect($davs)->toBeInstanceOf(Dav::class);
    });

    it('provides saldoEstoque method', function () {
        $saldoEstoque = Uniplus::saldoEstoque();

        expect($saldoEstoque)->toBeInstanceOf(SaldoEstoque::class);
    });

    it('provides vendas method', function () {
        $vendas = Uniplus::vendas();

        expect($vendas)->toBeInstanceOf(Venda::class);
    });

    it('provides vendaItens method', function () {
        $vendaItens = Uniplus::vendaItens();

        expect($vendaItens)->toBeInstanceOf(VendaItem::class);
    });

    it('allows method chaining on resources', function () {
        Http::fake([
            'https://uniplus.info/service-locator/*' => Http::response('https://api.test.uniplus.com'),
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*' => Http::response([]),
        ]);

        $builder = Uniplus::produtos()
            ->where('status', 'active')
            ->limit(10);

        $query = $builder->toQueryString();

        expect($query)->toContain('status.eq=active')
            ->and($query)->toContain('limit=10');
    });

    it('allows switching connections', function () {
        config([
            'uniplus.connections.other' => [
                'account' => 'other-account',
                'authorization_code' => base64_encode('other:secret'),
                'user_id' => 99,
                'branch_id' => 88,
            ],
        ]);

        $defaultClient = Uniplus::connection('test');
        $otherClient = Uniplus::connection('other');

        expect($defaultClient->getConnection()->getAccount())->toBe('test-account')
            ->and($otherClient->getConnection()->getAccount())->toBe('other-account');
    });
});
