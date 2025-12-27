<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Uniplus\Query\Builder;
use Uniplus\Resources\Dav;
use Uniplus\Resources\Entidade;
use Uniplus\UniplusManager;

beforeEach(function () {
    config([
        'uniplus.default' => 'test',
        'uniplus.routing_service' => 'https://test-router.example.com',
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
        '*test-router.example.com/*' => Http::response('https://api.test.uniplus.com'),
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

describe('Produto Resource', function () {
    it('can fetch all products', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.test.uniplus.com'),
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/produtos*' => Http::response([
                ['codigo' => '001', 'descricao' => 'Product 1'],
                ['codigo' => '002', 'descricao' => 'Product 2'],
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $produtos = $manager->produtos()->all();

        expect($produtos)->toBeInstanceOf(Collection::class)
            ->and($produtos)->toHaveCount(2);
    });

    it('can find a product by code', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.test.uniplus.com'),
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/produtos/001' => Http::response([
                'codigo' => '001',
                'descricao' => 'Product 1',
                'preco' => 99.99,
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $produto = $manager->produtos()->find('001');

        expect($produto['codigo'])->toBe('001')
            ->and($produto['descricao'])->toBe('Product 1');
    });

    it('can create a product', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.test.uniplus.com'),
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/produtos' => Http::response([
                'codigo' => '003',
                'descricao' => 'New Product',
            ], 201),
        ]);

        $manager = app(UniplusManager::class);
        $produto = $manager->produtos()->create([
            'descricao' => 'New Product',
            'preco' => 49.99,
        ]);

        expect($produto['codigo'])->toBe('003');
    });

    it('can update a product', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.test.uniplus.com'),
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/produtos' => Http::response([
                'codigo' => '001',
                'descricao' => 'Updated Product',
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $produto = $manager->produtos()->update([
            'codigo' => '001',
            'descricao' => 'Updated Product',
        ]);

        expect($produto['descricao'])->toBe('Updated Product');
    });

    it('can delete a product', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.test.uniplus.com'),
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/produtos' => Http::response(null, 204),
        ]);

        $manager = app(UniplusManager::class);
        $result = $manager->produtos()->delete('001');

        expect($result)->toBeTrue();
    });

    it('can filter active products', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->produtos()->active();

        expect($builder)->toBeInstanceOf(Builder::class)
            ->and($builder->toQueryString())->toContain('inativo.eq=0');
    });

    it('can filter inactive products', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->produtos()->inactive();

        expect($builder->toQueryString())->toContain('inativo.eq=1');
    });

    it('can filter products by group', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->produtos()->byGroup('GRP001');

        expect($builder->toQueryString())->toContain('codigoGrupoProduto.eq=GRP001');
    });

    it('can filter products by brand', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->produtos()->byBrand('BRAND01');

        expect($builder->toQueryString())->toContain('codigoMarca.eq=BRAND01');
    });

    it('can filter products changed after timestamp', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->produtos()->changedAfter(1704067200000);

        expect($builder->toQueryString())->toContain('currentTimeMillis.ge=1704067200000');
    });
});

describe('Entidade Resource', function () {
    it('has correct entity type constants', function () {
        expect(Entidade::TIPO_CLIENTE)->toBe(1)
            ->and(Entidade::TIPO_FORNECEDOR)->toBe(2)
            ->and(Entidade::TIPO_TRANSPORTADORA)->toBe(3)
            ->and(Entidade::TIPO_VENDEDOR)->toBe(4)
            ->and(Entidade::TIPO_FUNCIONARIO)->toBe(5);
    });

    it('can filter clients', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->entidades()->clients();

        expect($builder->toQueryString())->toContain('tipo.eq=1');
    });

    it('can filter suppliers', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->entidades()->suppliers();

        expect($builder->toQueryString())->toContain('tipo.eq=2');
    });

    it('can filter carriers', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->entidades()->carriers();

        expect($builder->toQueryString())->toContain('tipo.eq=3');
    });

    it('can filter salespeople', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->entidades()->salespeople();

        expect($builder->toQueryString())->toContain('tipo.eq=4');
    });

    it('can filter employees', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->entidades()->employees();

        expect($builder->toQueryString())->toContain('tipo.eq=5');
    });

    it('can filter by CPF/CNPJ', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->entidades()->byCpfCnpj('12345678901');

        expect($builder->toQueryString())->toContain('cnpjCpf.eq=12345678901');
    });
});

describe('Dav Resource', function () {
    it('has correct document type constants', function () {
        expect(Dav::TIPO_PRE_VENDA)->toBe(1)
            ->and(Dav::TIPO_ORCAMENTO)->toBe(2)
            ->and(Dav::TIPO_CONSIGNACAO)->toBe(3)
            ->and(Dav::TIPO_PEDIDO_VENDA)->toBe(4);
    });

    it('has correct status constants', function () {
        expect(Dav::STATUS_ABERTO)->toBe(0)
            ->and(Dav::STATUS_FECHADO)->toBe(1)
            ->and(Dav::STATUS_CANCELADO)->toBe(2);
    });

    it('can filter pre-sales', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->davs()->preSales();

        expect($builder->toQueryString())->toContain('tipoDocumento.eq=1');
    });

    it('can filter quotes', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->davs()->quotes();

        expect($builder->toQueryString())->toContain('tipoDocumento.eq=2');
    });

    it('can filter consignments', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->davs()->consignments();

        expect($builder->toQueryString())->toContain('tipoDocumento.eq=3');
    });

    it('can filter sales orders', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->davs()->salesOrders();

        expect($builder->toQueryString())->toContain('tipoDocumento.eq=4');
    });

    it('can filter open DAVs', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->davs()->open();

        expect($builder->toQueryString())->toContain('status.eq=0');
    });

    it('can filter closed DAVs', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->davs()->closed();

        expect($builder->toQueryString())->toContain('status.eq=1');
    });

    it('can filter canceled DAVs', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->davs()->canceled();

        expect($builder->toQueryString())->toContain('status.eq=2');
    });

    it('can filter by client', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->davs()->byClient('CLI001');

        expect($builder->toQueryString())->toContain('codigoCliente.eq=CLI001');
    });

    it('can filter by date range', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->davs()->byDateRange('2024-01-01', '2024-12-31');

        $query = $builder->toQueryString();
        expect($query)->toContain('data.ge=2024-01-01')
            ->and($query)->toContain('data.le=2024-12-31');
    });
});

describe('SaldoEstoque Resource', function () {
    it('can filter by product', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->saldoEstoque()->byProduct('PROD001');

        expect($builder->toQueryString())->toContain('produto.eq=PROD001');
    });

    it('can filter by branch', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->saldoEstoque()->byBranch(5);

        expect($builder->toQueryString())->toContain('filial.eq=5');
    });

    it('can filter by product and branch', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->saldoEstoque()->byProductAndBranch('PROD001', 5);

        $query = $builder->toQueryString();
        expect($query)->toContain('produto.eq=PROD001')
            ->and($query)->toContain('filial.eq=5');
    });

    it('can get balance for specific product', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.test.uniplus.com'),
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v2/saldo-estoque*' => Http::response([
                ['produto' => 'PROD001', 'quantidade' => 100, 'filial' => 1],
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $balance = $manager->saldoEstoque()->getBalance('PROD001');

        expect($balance['produto'])->toBe('PROD001')
            ->and($balance['quantidade'])->toBe(100);
    });

    it('can update stock balance', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.test.uniplus.com'),
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v2/saldo-estoque' => Http::response([
                'produto' => 'PROD001',
                'quantidade' => 150,
                'filial' => 1,
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $result = $manager->saldoEstoque()->updateBalance([
            'produto' => 'PROD001',
            'quantidade' => 150,
            'filial' => 1,
        ]);

        expect($result['quantidade'])->toBe(150);
    });
});

describe('Venda Resource (Read-only)', function () {
    it('can fetch sales', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.test.uniplus.com'),
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v2/venda*' => Http::response([
                ['id' => 1, 'total' => 199.99],
                ['id' => 2, 'total' => 299.99],
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $vendas = $manager->vendas()->all();

        expect($vendas)->toHaveCount(2);
    });
});

describe('VendaItem Resource (Read-only)', function () {
    it('can fetch sale items', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.test.uniplus.com'),
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v2/venda-item*' => Http::response([
                ['id' => 1, 'produto' => 'PROD001', 'quantidade' => 2],
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $itens = $manager->vendaItens()->all();

        expect($itens)->toHaveCount(1);
    });
});

describe('Resource Query Building', function () {
    it('returns Builder from query method', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->produtos()->query();

        expect($builder)->toBeInstanceOf(Builder::class);
    });

    it('returns Builder from where method', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->produtos()->where('status', 'active');

        expect($builder)->toBeInstanceOf(Builder::class);
    });

    it('returns Builder from limit method', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->produtos()->limit(10);

        expect($builder)->toBeInstanceOf(Builder::class)
            ->and($builder->toQueryString())->toContain('limit=10');
    });

    it('returns Builder from offset method', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->produtos()->offset(50);

        expect($builder)->toBeInstanceOf(Builder::class)
            ->and($builder->toQueryString())->toContain('offset=50');
    });

    it('returns correct endpoint', function () {
        $manager = app(UniplusManager::class);
        $produtos = $manager->produtos();

        expect($produtos->getEndpoint())->toBe('public-api/v1/produtos');
    });
});
