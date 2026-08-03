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

describe('Produto Resource', function () {
    it('can fetch all products', function () {
        Http::fake([
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

    it('can create a product, wrapped in a single "produto" level', function () {
        Http::fake([
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

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'https://api.test.uniplus.com/public-api/v1/produtos'
                && $request->data() === [
                    'produto' => [
                        'descricao' => 'New Product',
                        'preco' => 49.99,
                    ],
                ];
        });
    });

    it('can update a product, wrapped in a single "produto" level', function () {
        Http::fake([
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

        Http::assertSent(function ($request) {
            return $request->method() === 'PUT'
                && $request->url() === 'https://api.test.uniplus.com/public-api/v1/produtos'
                && $request->data() === [
                    'produto' => [
                        'codigo' => '001',
                        'descricao' => 'Updated Product',
                    ],
                ];
        });
    });

    it('can delete a product via DELETE with the code as a path parameter', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/produtos/001' => Http::response(null, 204),
        ]);

        $manager = app(UniplusManager::class);
        $result = $manager->produtos()->delete('001');

        expect($result)->toBeTrue();

        Http::assertSent(function ($request) {
            return $request->method() === 'DELETE'
                && $request->url() === 'https://api.test.uniplus.com/public-api/v1/produtos/001'
                && $request->data() === [];
        });
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

    it('can update prices for multiple products, sent as a raw array without a wrapper', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/produtos/precos' => Http::response([
                'success' => true,
                'updated' => 2,
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $result = $manager->produtos()->updatePrecos([
            ['codigo' => '001', 'preco' => 99.90],
            ['codigo' => '002', 'preco' => 149.90],
        ]);

        expect($result['success'])->toBeTrue()
            ->and($result['updated'])->toBe(2);

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->data() === [
                    ['codigo' => '001', 'preco' => 99.90],
                    ['codigo' => '002', 'preco' => 149.90],
                ];
        });
    });

    it('can update prices with branch-specific pricing', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/produtos/precos' => Http::response([
                'success' => true,
                'updated' => 1,
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $result = $manager->produtos()->updatePrecos([
            [
                'codigo' => '001',
                'precos' => [
                    [
                        'filial' => '1',
                        'preco' => 99.90,
                        'pautasPreco' => [
                            ['codigoPauta' => '1', 'preco' => 89.90],
                            ['codigoPauta' => '2', 'preco' => 94.90],
                        ],
                    ],
                ],
            ],
        ]);

        expect($result['success'])->toBeTrue();
    });

    it('throws exception when updatePrecos receives empty array', function () {
        $manager = app(UniplusManager::class);
        $manager->produtos()->updatePrecos([]);
    })->throws(InvalidArgumentException::class, 'Products array cannot be empty.');

    it('can create multiple products at once, sent as a raw array without a wrapper', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/produtos/lista' => Http::response([
                'success' => true,
                'created' => 2,
                'products' => [
                    ['codigo' => '001', 'nome' => 'Produto 1'],
                    ['codigo' => '002', 'nome' => 'Produto 2'],
                ],
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $result = $manager->produtos()->createMany([
            ['nome' => 'Produto 1', 'preco' => 99.90, 'unidadeMedida' => 'UN'],
            ['nome' => 'Produto 2', 'preco' => 149.90, 'unidadeMedida' => 'UN'],
        ]);

        expect($result['success'])->toBeTrue()
            ->and($result['created'])->toBe(2)
            ->and($result['products'])->toHaveCount(2);

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->data() === [
                    ['nome' => 'Produto 1', 'preco' => 99.90, 'unidadeMedida' => 'UN'],
                    ['nome' => 'Produto 2', 'preco' => 149.90, 'unidadeMedida' => 'UN'],
                ];
        });
    });

    it('throws exception when createMany receives empty array', function () {
        $manager = app(UniplusManager::class);
        $manager->produtos()->createMany([]);
    })->throws(InvalidArgumentException::class, 'Products array cannot be empty.');

    it('can update multiple products at once, sent as a raw array without a wrapper', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/produtos/lista' => Http::response([
                'success' => true,
                'updated' => 2,
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $result = $manager->produtos()->updateMany([
            ['codigo' => '001', 'nome' => 'Produto 1 atualizado'],
            ['codigo' => '002', 'nome' => 'Produto 2 atualizado'],
        ]);

        expect($result['success'])->toBeTrue()
            ->and($result['updated'])->toBe(2);

        Http::assertSent(function ($request) {
            return $request->method() === 'PUT'
                && $request->url() === 'https://api.test.uniplus.com/public-api/v1/produtos/lista'
                && $request->data() === [
                    ['codigo' => '001', 'nome' => 'Produto 1 atualizado'],
                    ['codigo' => '002', 'nome' => 'Produto 2 atualizado'],
                ];
        });
    });

    it('throws exception when updateMany receives empty array without sending a request', function () {
        $manager = app(UniplusManager::class);

        expect(fn () => $manager->produtos()->updateMany([]))
            ->toThrow(InvalidArgumentException::class, 'Products array cannot be empty.');

        Http::assertNothingSent();
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

    it('can create an entity, wrapped in a single "entidade" level', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/entidades' => Http::response([
                'codigo' => '001',
                'nome' => 'Cliente Teste',
            ], 201),
        ]);

        $manager = app(UniplusManager::class);
        $entidade = $manager->entidades()->create([
            'nome' => 'Cliente Teste',
            'tipo' => Entidade::TIPO_CLIENTE,
        ]);

        expect($entidade['codigo'])->toBe('001');

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'https://api.test.uniplus.com/public-api/v1/entidades'
                && $request->data() === [
                    'entidade' => [
                        'nome' => 'Cliente Teste',
                        'tipo' => Entidade::TIPO_CLIENTE,
                    ],
                ];
        });
    });

    it('can update an entity, wrapped in a single "entidade" level', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/entidades' => Http::response([
                'codigo' => '001',
                'nome' => 'Cliente Atualizado',
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $entidade = $manager->entidades()->update([
            'codigo' => '001',
            'nome' => 'Cliente Atualizado',
        ]);

        expect($entidade['nome'])->toBe('Cliente Atualizado');

        Http::assertSent(function ($request) {
            return $request->method() === 'PUT'
                && $request->url() === 'https://api.test.uniplus.com/public-api/v1/entidades'
                && $request->data() === [
                    'entidade' => [
                        'codigo' => '001',
                        'nome' => 'Cliente Atualizado',
                    ],
                ];
        });
    });

    it('can delete an entity via DELETE with the code as a path parameter', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/entidades/001' => Http::response(null, 204),
        ]);

        $manager = app(UniplusManager::class);
        $result = $manager->entidades()->delete('001');

        expect($result)->toBeTrue();

        Http::assertSent(function ($request) {
            return $request->method() === 'DELETE'
                && $request->url() === 'https://api.test.uniplus.com/public-api/v1/entidades/001'
                && $request->data() === [];
        });
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

    it('can create a DAV, wrapped in a single "dav" level', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/davs' => Http::response([
                'codigo' => '001',
                'tipoDocumento' => Dav::TIPO_ORCAMENTO,
            ], 201),
        ]);

        $manager = app(UniplusManager::class);
        $dav = $manager->davs()->create([
            'tipoDocumento' => Dav::TIPO_ORCAMENTO,
            'codigoCliente' => 'CLI001',
        ]);

        expect($dav['codigo'])->toBe('001');

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'https://api.test.uniplus.com/public-api/v1/davs'
                && $request->data() === [
                    'dav' => [
                        'tipoDocumento' => Dav::TIPO_ORCAMENTO,
                        'codigoCliente' => 'CLI001',
                    ],
                ];
        });
    });

    it('can update a DAV, wrapped in a single "dav" level', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/davs' => Http::response([
                'codigo' => '001',
                'status' => Dav::STATUS_FECHADO,
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $dav = $manager->davs()->update([
            'codigo' => '001',
            'status' => Dav::STATUS_FECHADO,
        ]);

        expect($dav['status'])->toBe(Dav::STATUS_FECHADO);

        Http::assertSent(function ($request) {
            return $request->method() === 'PUT'
                && $request->url() === 'https://api.test.uniplus.com/public-api/v1/davs'
                && $request->data() === [
                    'dav' => [
                        'codigo' => '001',
                        'status' => Dav::STATUS_FECHADO,
                    ],
                ];
        });
    });

    it('can delete a DAV via DELETE with the code as a path parameter', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/davs/001' => Http::response(null, 204),
        ]);

        $manager = app(UniplusManager::class);
        $result = $manager->davs()->delete('001');

        expect($result)->toBeTrue();

        Http::assertSent(function ($request) {
            return $request->method() === 'DELETE'
                && $request->url() === 'https://api.test.uniplus.com/public-api/v1/davs/001'
                && $request->data() === [];
        });
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

    it('can update stock balance, wrapped in a single "saldoEstoque" level', function () {
        Http::fake([
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

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'https://api.test.uniplus.com/public-api/v2/saldo-estoque'
                && $request->data() === [
                    'saldoEstoque' => [
                        'produto' => 'PROD001',
                        'quantidade' => 150,
                        'filial' => 1,
                    ],
                ];
        });
    });

    it('can delete a stock balance via DELETE with the code as a path parameter', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v2/saldo-estoque/PROD001' => Http::response(null, 204),
        ]);

        $manager = app(UniplusManager::class);
        $result = $manager->saldoEstoque()->delete('PROD001');

        expect($result)->toBeTrue();

        Http::assertSent(function ($request) {
            return $request->method() === 'DELETE'
                && $request->url() === 'https://api.test.uniplus.com/public-api/v2/saldo-estoque/PROD001'
                && $request->data() === [];
        });
    });
});

describe('Venda Resource (Read-only)', function () {
    it('can fetch sales', function () {
        Http::fake([
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
