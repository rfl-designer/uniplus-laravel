<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Uniplus\Exceptions\UniplusException;
use Uniplus\Query\Builder;
use Uniplus\Resources\Commons\CommonsFactory;
use Uniplus\Resources\Commons\CommonsResource;
use Uniplus\Resources\ContaGourmet;
use Uniplus\Resources\Ean;
use Uniplus\Resources\Embalagem;
use Uniplus\Resources\GrupoShop;
use Uniplus\Resources\ItemNotaEntrada;
use Uniplus\Resources\ItemNotaEntradaCompra;
use Uniplus\Resources\MovimentacaoEstoque;
use Uniplus\Resources\OrdemServico;
use Uniplus\Resources\RegistroProducao;
use Uniplus\Resources\SaldoEstoqueVariacao;
use Uniplus\Resources\TipoDocumentoFinanceiro;
use Uniplus\Resources\Variacao;
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

// =============================================================================
// Phase 1 - Individual Resources
// =============================================================================

describe('GrupoShop Resource', function () {
    it('returns correct endpoint', function () {
        $manager = app(UniplusManager::class);
        $resource = $manager->grupoShop();

        expect($resource)->toBeInstanceOf(GrupoShop::class)
            ->and($resource->getEndpoint())->toBe('public-api/v1/grupo-shop');
    });

    it('can filter by parent category', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->grupoShop()->byParent(5);

        expect($builder)->toBeInstanceOf(Builder::class)
            ->and($builder->toQueryString())->toContain('idPai.eq=5');
    });

    it('can filter root categories', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->grupoShop()->roots();

        expect($builder->toQueryString())->toContain('idPai.eq=0');
    });

    it('throws exception on create', function () {
        $manager = app(UniplusManager::class);
        $manager->grupoShop()->create(['nome' => 'Test']);
    })->throws(UniplusException::class, 'Create operation is not supported');
});

describe('OrdemServico Resource', function () {
    it('returns correct endpoint', function () {
        $manager = app(UniplusManager::class);
        $resource = $manager->ordemServico();

        expect($resource)->toBeInstanceOf(OrdemServico::class)
            ->and($resource->getEndpoint())->toBe('public-api/v1/ordem-servico');
    });

    it('has correct status constants', function () {
        expect(OrdemServico::STATUS_ABERTA)->toBe(1)
            ->and(OrdemServico::STATUS_EM_EXECUCAO)->toBe(2)
            ->and(OrdemServico::STATUS_FINALIZADA)->toBe(3)
            ->and(OrdemServico::STATUS_CANCELADA)->toBe(4);
    });

    it('can filter open orders', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->ordemServico()->open();

        expect($builder->toQueryString())->toContain('status.eq=1');
    });

    it('can filter by client', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->ordemServico()->byClient('CLI001');

        expect($builder->toQueryString())->toContain('codigoCliente.eq=CLI001');
    });

    it('can filter by branch', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->ordemServico()->byBranch('1');

        expect($builder->toQueryString())->toContain('codigoFilial.eq=1');
    });
});

describe('Ean Resource', function () {
    it('returns correct endpoint', function () {
        $manager = app(UniplusManager::class);
        $resource = $manager->eans();

        expect($resource)->toBeInstanceOf(Ean::class)
            ->and($resource->getEndpoint())->toBe('public-api/v1/eans');
    });

    it('can filter by product', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->eans()->byProduct('PROD001');

        expect($builder->toQueryString())->toContain('produto.eq=PROD001');
    });

    it('can filter by barcode', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->eans()->byBarcode('7891234567890');

        expect($builder->toQueryString())->toContain('ean.eq=7891234567890');
    });

    it('can create an EAN', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.test.uniplus.com'),
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/eans' => Http::response([
                'codigoProduto' => 'PROD001',
                'ean' => '7891234567890',
            ], 201),
        ]);

        $manager = app(UniplusManager::class);
        $result = $manager->eans()->create([
            'codigoProduto' => 'PROD001',
            'ean' => '7891234567890',
        ]);

        expect($result['ean'])->toBe('7891234567890');
    });
});

describe('Embalagem Resource', function () {
    it('returns correct endpoint', function () {
        $manager = app(UniplusManager::class);
        $resource = $manager->embalagens();

        expect($resource)->toBeInstanceOf(Embalagem::class)
            ->and($resource->getEndpoint())->toBe('public-api/v1/embalagens');
    });

    it('can filter by product', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->embalagens()->byProduct('PROD001');

        expect($builder->toQueryString())->toContain('produto.eq=PROD001');
    });

    it('can filter by type', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->embalagens()->byType(1);

        expect($builder->toQueryString())->toContain('tipoEmbalagem.eq=1');
    });
});

describe('RegistroProducao Resource', function () {
    it('returns correct endpoint', function () {
        $manager = app(UniplusManager::class);
        $resource = $manager->registroProducao();

        expect($resource)->toBeInstanceOf(RegistroProducao::class)
            ->and($resource->getEndpoint())->toBe('public-api/v1/registro-producao');
    });

    it('can filter by product ID', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->registroProducao()->byProduct(123);

        expect($builder->toQueryString())->toContain('idProduto.eq=123');
    });

    it('can filter by branch ID', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->registroProducao()->byBranchId(1);

        expect($builder->toQueryString())->toContain('idFilial.eq=1');
    });

    it('can filter by date range', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->registroProducao()->byDateRange('2024-01-01', '2024-12-31');

        $query = $builder->toQueryString();
        expect($query)->toContain('dataHora.ge=2024-01-01')
            ->and($query)->toContain('dataHora.le=2024-12-31');
    });
});

describe('Variacao Resource', function () {
    it('returns correct endpoint', function () {
        $manager = app(UniplusManager::class);
        $resource = $manager->variacoes();

        expect($resource)->toBeInstanceOf(Variacao::class)
            ->and($resource->getEndpoint())->toBe('public-api/v1/variacoes');
    });

    it('can filter by product', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->variacoes()->byProduct('PROD001');

        expect($builder->toQueryString())->toContain('produto.eq=PROD001');
    });

    it('can filter by grid', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->variacoes()->byGrid('COR');

        expect($builder->toQueryString())->toContain('codigoGrade.eq=COR');
    });

    it('can filter row variations', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->variacoes()->rows();

        expect($builder->toQueryString())->toContain('tipoRegistro.eq=0');
    });
});

describe('ItemNotaEntrada Resource', function () {
    it('returns correct endpoint', function () {
        $manager = app(UniplusManager::class);
        $resource = $manager->itemNotaEntrada();

        expect($resource)->toBeInstanceOf(ItemNotaEntrada::class)
            ->and($resource->getEndpoint())->toBe('public-api/v1/item-nota-entrada');
    });

    it('can filter by product code', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->itemNotaEntrada()->byProduct('123');

        expect($builder->toQueryString())->toContain('produtoCodigo.eq=123');
    });

    it('can filter by branch code', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->itemNotaEntrada()->byBranch('1');

        expect($builder->toQueryString())->toContain('filialCodigo.eq=1');
    });

    it('can filter by supplier code', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->itemNotaEntrada()->bySupplier('456');

        expect($builder->toQueryString())->toContain('fornecedorCodigo.eq=456');
    });

    it('throws exception on create', function () {
        $manager = app(UniplusManager::class);
        $manager->itemNotaEntrada()->create(['produto' => 'Test']);
    })->throws(UniplusException::class, 'Create operation is not supported');
});

describe('ItemNotaEntradaCompra Resource', function () {
    it('returns correct endpoint', function () {
        $manager = app(UniplusManager::class);
        $resource = $manager->itemNotaEntradaCompra();

        expect($resource)->toBeInstanceOf(ItemNotaEntradaCompra::class)
            ->and($resource->getEndpoint())->toBe('public-api/v1/item-nota-entrada/compra');
    });

    it('can filter by invoice ID', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->itemNotaEntradaCompra()->byInvoice(12345);

        expect($builder->toQueryString())->toContain('idNotaFiscal.eq=12345');
    });
});

// =============================================================================
// Phase 2 - Sales, Financial and Grouped Resources
// =============================================================================

describe('MovimentacaoEstoque Resource', function () {
    it('returns correct endpoint', function () {
        $manager = app(UniplusManager::class);
        $resource = $manager->movimentacaoEstoque();

        expect($resource)->toBeInstanceOf(MovimentacaoEstoque::class)
            ->and($resource->getEndpoint())->toBe('public-api/v2/movimentacao-estoque');
    });

    it('can filter by product', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->movimentacaoEstoque()->byProduct('PROD001');

        expect($builder->toQueryString())->toContain('produto.eq=PROD001');
    });

    it('can filter entries', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->movimentacaoEstoque()->entries();

        expect($builder->toQueryString())->toContain('tipoMovimentacao.eq=E');
    });

    it('can filter exits', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->movimentacaoEstoque()->exits();

        expect($builder->toQueryString())->toContain('tipoMovimentacao.eq=S');
    });

    it('throws exception on create', function () {
        $manager = app(UniplusManager::class);
        $manager->movimentacaoEstoque()->create(['produto' => 'Test']);
    })->throws(UniplusException::class, 'Create operation is not supported');
});

describe('TipoDocumentoFinanceiro Resource', function () {
    it('returns correct endpoint', function () {
        $manager = app(UniplusManager::class);
        $resource = $manager->tipoDocumentoFinanceiro();

        expect($resource)->toBeInstanceOf(TipoDocumentoFinanceiro::class)
            ->and($resource->getEndpoint())->toBe('public-api/v1/tipo-documento-financeiro');
    });

    it('has correct action constants', function () {
        expect(TipoDocumentoFinanceiro::ACTION_CASH)->toBe(0)
            ->and(TipoDocumentoFinanceiro::ACTION_RECEIVE)->toBe(2)
            ->and(TipoDocumentoFinanceiro::ACTION_PAY)->toBe(3)
            ->and(TipoDocumentoFinanceiro::ACTION_PIX)->toBe(23)
            ->and(TipoDocumentoFinanceiro::ACTION_CREDIT_CARD)->toBe(20)
            ->and(TipoDocumentoFinanceiro::ACTION_DEBIT_CARD)->toBe(21);
    });

    it('can filter by action', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->tipoDocumentoFinanceiro()->byAction(TipoDocumentoFinanceiro::ACTION_PIX);

        expect($builder->toQueryString())->toContain('acao.eq=23');
    });

    it('can filter active types', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->tipoDocumentoFinanceiro()->active();

        expect($builder->toQueryString())->toContain('inativo.eq=0');
    });

    it('can filter PIX types', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->tipoDocumentoFinanceiro()->pix();

        expect($builder->toQueryString())->toContain('acao.eq=23');
    });

    it('can filter receivables', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->tipoDocumentoFinanceiro()->receivables();

        expect($builder->toQueryString())->toContain('localUso.eq=1');
    });
});

describe('SaldoEstoqueVariacao Resource', function () {
    it('returns correct endpoint', function () {
        $manager = app(UniplusManager::class);
        $resource = $manager->saldoEstoqueVariacao();

        expect($resource)->toBeInstanceOf(SaldoEstoqueVariacao::class)
            ->and($resource->getEndpoint())->toBe('public-api/v2/saldo-estoque/variacao');
    });

    it('can filter by product and branch', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->saldoEstoqueVariacao()->byProductAndBranch('PROD001', '1');

        $query = $builder->toQueryString();
        expect($query)->toContain('produto.eq=PROD001')
            ->and($query)->toContain('filial.eq=1');
    });

    it('can filter with stock', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->saldoEstoqueVariacao()->withStock();

        expect($builder->toQueryString())->toContain('saldo.gt=0');
    });

    it('can filter below minimum', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->saldoEstoqueVariacao()->belowMinimum(10.5);

        expect($builder->toQueryString())->toContain('saldo.lt=10.5');
    });
});

describe('ContaGourmet Resource', function () {
    it('returns correct endpoint', function () {
        $manager = app(UniplusManager::class);
        $resource = $manager->contaGourmet();

        expect($resource)->toBeInstanceOf(ContaGourmet::class)
            ->and($resource->getEndpoint())->toBe('public-api/v1/gourmet/conta');
    });

    it('has correct type constants', function () {
        expect(ContaGourmet::TYPE_TABLE)->toBe('MESA')
            ->and(ContaGourmet::TYPE_TAB)->toBe('COMANDA');
    });

    it('has correct optional constants', function () {
        expect(ContaGourmet::OPTIONAL_WITH)->toBe('COM')
            ->and(ContaGourmet::OPTIONAL_WITHOUT)->toBe('SEM');
    });

    it('can filter tables', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->contaGourmet()->tables();

        expect($builder->toQueryString())->toContain('tipo.eq=MESA');
    });

    it('can filter tabs', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->contaGourmet()->tabs();

        expect($builder->toQueryString())->toContain('tipo.eq=COMANDA');
    });

    it('can build an item', function () {
        $manager = app(UniplusManager::class);
        $item = $manager->contaGourmet()->buildItem('PROD001', 'X-Burger', 2.0, 15.0);

        expect($item['codigoProduto'])->toBe('PROD001')
            ->and($item['nomeProduto'])->toBe('X-Burger')
            ->and($item['quantidade'])->toBe('2.000')
            ->and($item['valorUnitario'])->toBe('15.000')
            ->and($item['valorTotal'])->toBe('30.000');
    });

    it('can build an addon', function () {
        $manager = app(UniplusManager::class);
        $addon = $manager->contaGourmet()->buildAddon('BACON', 'Bacon Extra', 1.0, 5.0);

        expect($addon['tipo'])->toBe('COM')
            ->and($addon['nomeProduto'])->toBe('Bacon Extra');
    });

    it('can build a removal', function () {
        $manager = app(UniplusManager::class);
        $removal = $manager->contaGourmet()->buildRemoval('CEBOLA', 'Cebola');

        expect($removal['tipo'])->toBe('SEM')
            ->and($removal['valorTotal'])->toBe('0.00');
    });

    it('can find a table account', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.test.uniplus.com'),
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/gourmet/conta*' => Http::response([
                'numero' => 10,
                'tipo' => 'MESA',
                'itens' => [],
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $conta = $manager->contaGourmet()->findTable(10, '1');

        expect($conta['numero'])->toBe(10)
            ->and($conta['tipo'])->toBe('MESA');
    });
});

// =============================================================================
// Phase 3 - Commons Resources
// =============================================================================

describe('CommonsFactory', function () {
    it('returns CommonsFactory from commons method', function () {
        $manager = app(UniplusManager::class);
        $commons = $manager->commons();

        expect($commons)->toBeInstanceOf(CommonsFactory::class);
    });

    it('provides access to banco resource', function () {
        $manager = app(UniplusManager::class);
        $banco = $manager->commons()->banco();

        expect($banco)->toBeInstanceOf(CommonsResource::class)
            ->and($banco->getEndpoint())->toBe('public-api/v1/commons/banco');
    });

    it('provides access to cidade resource', function () {
        $manager = app(UniplusManager::class);
        $cidade = $manager->commons()->cidade();

        expect($cidade)->toBeInstanceOf(CommonsResource::class)
            ->and($cidade->getEndpoint())->toBe('public-api/v1/commons/cidade');
    });

    it('provides access to estado resource', function () {
        $manager = app(UniplusManager::class);
        $estado = $manager->commons()->estado();

        expect($estado)->toBeInstanceOf(CommonsResource::class)
            ->and($estado->getEndpoint())->toBe('public-api/v1/commons/estado');
    });

    it('provides access to filial resource', function () {
        $manager = app(UniplusManager::class);
        $filial = $manager->commons()->filial();

        expect($filial)->toBeInstanceOf(CommonsResource::class)
            ->and($filial->getEndpoint())->toBe('public-api/v1/commons/filial');
    });

    it('can access resources via table method', function () {
        $manager = app(UniplusManager::class);
        $resource = $manager->commons()->table('tipopedido');

        expect($resource)->toBeInstanceOf(CommonsResource::class)
            ->and($resource->getEndpoint())->toBe('public-api/v1/commons/tipopedido');
    });

    it('throws exception for unknown resource', function () {
        $manager = app(UniplusManager::class);
        $manager->commons()->unknownResource();
    })->throws(UniplusException::class, 'Unknown Commons resource');

    it('returns available tables list', function () {
        $tables = CommonsFactory::getAvailableTables();

        expect($tables)->toBeArray()
            ->and($tables)->toHaveKey('banco')
            ->and($tables)->toHaveKey('cidade')
            ->and($tables)->toHaveKey('estado')
            ->and($tables['banco'])->toBe('banco');
    });

    it('validates table names', function () {
        expect(CommonsFactory::isValidTable('banco'))->toBeTrue()
            ->and(CommonsFactory::isValidTable('cidade'))->toBeTrue()
            ->and(CommonsFactory::isValidTable('invalid'))->toBeFalse();
    });
});

describe('CommonsResource', function () {
    it('can fetch all records', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.test.uniplus.com'),
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/commons/banco*' => Http::response([
                ['id' => 1, 'codigo' => '001', 'nome' => 'Banco do Brasil'],
                ['id' => 2, 'codigo' => '341', 'nome' => 'Itaú'],
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $bancos = $manager->commons()->banco()->all();

        expect($bancos)->toBeInstanceOf(Collection::class)
            ->and($bancos)->toHaveCount(2);
    });

    it('can find a record by ID', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.test.uniplus.com'),
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/commons/banco/1' => Http::response([
                'id' => 1,
                'codigo' => '001',
                'nome' => 'Banco do Brasil',
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $banco = $manager->commons()->banco()->find(1);

        expect($banco['id'])->toBe(1)
            ->and($banco['codigo'])->toBe('001');
    });

    it('can use query builder', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->commons()->cidade()->where('idestado', 1);

        expect($builder)->toBeInstanceOf(Builder::class)
            ->and($builder->toQueryString())->toContain('idestado.eq=1');
    });

    it('can use limit and offset', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->commons()->estado()->limit(10)->offset(20);

        $query = $builder->toQueryString();
        expect($query)->toContain('limit=10')
            ->and($query)->toContain('offset=20');
    });

    it('throws exception on create', function () {
        $manager = app(UniplusManager::class);
        $manager->commons()->banco()->create(['nome' => 'Test']);
    })->throws(UniplusException::class, 'Create operation is not supported for Commons resources');

    it('throws exception on update', function () {
        $manager = app(UniplusManager::class);
        $manager->commons()->banco()->update(['nome' => 'Test']);
    })->throws(UniplusException::class, 'Update operation is not supported for Commons resources');

    it('throws exception on delete', function () {
        $manager = app(UniplusManager::class);
        $manager->commons()->banco()->delete('1');
    })->throws(UniplusException::class, 'Delete operation is not supported for Commons resources');

    it('returns table name', function () {
        $manager = app(UniplusManager::class);
        $resource = $manager->commons()->cidade();

        expect($resource->getTable())->toBe('cidade');
    });
});

// =============================================================================
// UniplusManager Proxy Methods
// =============================================================================

describe('UniplusManager New Proxy Methods', function () {
    it('proxies grupoShop method', function () {
        $manager = app(UniplusManager::class);
        expect($manager->grupoShop())->toBeInstanceOf(GrupoShop::class);
    });

    it('proxies ordemServico method', function () {
        $manager = app(UniplusManager::class);
        expect($manager->ordemServico())->toBeInstanceOf(OrdemServico::class);
    });

    it('proxies eans method', function () {
        $manager = app(UniplusManager::class);
        expect($manager->eans())->toBeInstanceOf(Ean::class);
    });

    it('proxies embalagens method', function () {
        $manager = app(UniplusManager::class);
        expect($manager->embalagens())->toBeInstanceOf(Embalagem::class);
    });

    it('proxies registroProducao method', function () {
        $manager = app(UniplusManager::class);
        expect($manager->registroProducao())->toBeInstanceOf(RegistroProducao::class);
    });

    it('proxies variacoes method', function () {
        $manager = app(UniplusManager::class);
        expect($manager->variacoes())->toBeInstanceOf(Variacao::class);
    });

    it('proxies itemNotaEntrada method', function () {
        $manager = app(UniplusManager::class);
        expect($manager->itemNotaEntrada())->toBeInstanceOf(ItemNotaEntrada::class);
    });

    it('proxies itemNotaEntradaCompra method', function () {
        $manager = app(UniplusManager::class);
        expect($manager->itemNotaEntradaCompra())->toBeInstanceOf(ItemNotaEntradaCompra::class);
    });

    it('proxies movimentacaoEstoque method', function () {
        $manager = app(UniplusManager::class);
        expect($manager->movimentacaoEstoque())->toBeInstanceOf(MovimentacaoEstoque::class);
    });

    it('proxies tipoDocumentoFinanceiro method', function () {
        $manager = app(UniplusManager::class);
        expect($manager->tipoDocumentoFinanceiro())->toBeInstanceOf(TipoDocumentoFinanceiro::class);
    });

    it('proxies saldoEstoqueVariacao method', function () {
        $manager = app(UniplusManager::class);
        expect($manager->saldoEstoqueVariacao())->toBeInstanceOf(SaldoEstoqueVariacao::class);
    });

    it('proxies contaGourmet method', function () {
        $manager = app(UniplusManager::class);
        expect($manager->contaGourmet())->toBeInstanceOf(ContaGourmet::class);
    });

    it('proxies commons method', function () {
        $manager = app(UniplusManager::class);
        expect($manager->commons())->toBeInstanceOf(CommonsFactory::class);
    });
});

// =============================================================================
// Extended Tests - Complete Method Coverage
// =============================================================================

describe('GrupoShop Resource - Extended', function () {
    it('can filter by code', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->grupoShop()->byCode('CAT001');

        expect($builder->toQueryString())->toContain('codigo.eq=CAT001');
    });

    it('can filter by name', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->grupoShop()->byName('Eletronicos');

        expect($builder->toQueryString())->toContain('nome.eq=Eletronicos');
    });

    it('throws exception on update', function () {
        $manager = app(UniplusManager::class);
        $manager->grupoShop()->update(['nome' => 'Test']);
    })->throws(UniplusException::class, 'Update operation is not supported');

    it('throws exception on delete', function () {
        $manager = app(UniplusManager::class);
        $manager->grupoShop()->delete('1');
    })->throws(UniplusException::class, 'Delete operation is not supported');

    it('can fetch all categories', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.test.uniplus.com'),
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/grupo-shop*' => Http::response([
                ['id' => 1, 'codigo' => 'CAT001', 'nome' => 'Categoria 1'],
                ['id' => 2, 'codigo' => 'CAT002', 'nome' => 'Categoria 2'],
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $categorias = $manager->grupoShop()->all();

        expect($categorias)->toBeInstanceOf(Collection::class)
            ->and($categorias)->toHaveCount(2);
    });
});

describe('OrdemServico Resource - Extended', function () {
    it('has all status constants', function () {
        expect(OrdemServico::STATUS_FATURADA)->toBe(5)
            ->and(OrdemServico::STATUS_AGENDADA)->toBe(6)
            ->and(OrdemServico::STATUS_PAUSADA)->toBe(7)
            ->and(OrdemServico::STATUS_PASSOU_PELO_PDV)->toBe(8)
            ->and(OrdemServico::STATUS_FATURADA_POR_DAV_OS)->toBe(9)
            ->and(OrdemServico::STATUS_MESCLADO)->toBe(10)
            ->and(OrdemServico::STATUS_DUPLICADO)->toBe(11)
            ->and(OrdemServico::STATUS_SERVICO_NAO_EXECUTADO)->toBe(12)
            ->and(OrdemServico::STATUS_ORCAMENTO)->toBe(13)
            ->and(OrdemServico::STATUS_FATURADO_PARCIALMENTE)->toBe(14)
            ->and(OrdemServico::STATUS_RETIRADA)->toBe(15);
    });

    it('can filter by status', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->ordemServico()->byStatus(OrdemServico::STATUS_FATURADA);

        expect($builder->toQueryString())->toContain('status.eq=5');
    });

    it('can filter in execution', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->ordemServico()->inExecution();

        expect($builder->toQueryString())->toContain('status.eq=2');
    });

    it('can filter finished', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->ordemServico()->finished();

        expect($builder->toQueryString())->toContain('status.eq=3');
    });

    it('can filter canceled', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->ordemServico()->canceled();

        expect($builder->toQueryString())->toContain('status.eq=4');
    });

    it('can filter invoiced', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->ordemServico()->invoiced();

        expect($builder->toQueryString())->toContain('status.eq=5');
    });

    it('can filter scheduled', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->ordemServico()->scheduled();

        expect($builder->toQueryString())->toContain('status.eq=6');
    });

    it('can filter paused', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->ordemServico()->paused();

        expect($builder->toQueryString())->toContain('status.eq=7');
    });

    it('can filter by client ID', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->ordemServico()->byClientId(123);

        expect($builder->toQueryString())->toContain('idCliente.eq=123');
    });

    it('can filter by technician', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->ordemServico()->byTechnician(456);

        expect($builder->toQueryString())->toContain('idAtendente.eq=456');
    });

    it('can filter by date range', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->ordemServico()->byDateRange('2024-01-01', '2024-12-31');

        $query = $builder->toQueryString();
        expect($query)->toContain('dataOrdemServico.ge=2024-01-01')
            ->and($query)->toContain('dataOrdemServico.le=2024-12-31');
    });

    it('can filter changed after timestamp', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->ordemServico()->changedAfter(1704067200000);

        expect($builder->toQueryString())->toContain('currentTimeMillis.ge=1704067200000');
    });
});

describe('Ean Resource - Extended', function () {
    it('can filter by variation', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->eans()->byVariation('VAR001');

        expect($builder->toQueryString())->toContain('variacao.eq=VAR001');
    });

    it('can add an EAN using helper method', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.test.uniplus.com'),
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/eans' => Http::response([
                'produto' => 'PROD001',
                'ean' => '7891234567890',
            ], 201),
        ]);

        $manager = app(UniplusManager::class);
        $result = $manager->eans()->addEan([
            'produto' => 'PROD001',
            'ean' => '7891234567890',
        ]);

        expect($result['ean'])->toBe('7891234567890');
    });

    it('can remove an EAN', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.test.uniplus.com'),
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/eans' => Http::response(null, 204),
        ]);

        $manager = app(UniplusManager::class);
        $result = $manager->eans()->removeEan('7891234567890');

        expect($result)->toBeTrue();
    });

    it('throws exception on update', function () {
        $manager = app(UniplusManager::class);
        $manager->eans()->update(['ean' => 'Test']);
    })->throws(UniplusException::class, 'Update operation is not supported for EANs');
});

describe('Embalagem Resource - Extended', function () {
    it('has correct type constants', function () {
        expect(Embalagem::TIPO_COMPRA_VENDA)->toBe(0)
            ->and(Embalagem::TIPO_SOMENTE_COMPRA)->toBe(1)
            ->and(Embalagem::TIPO_SOMENTE_VENDA)->toBe(2);
    });

    it('can filter by unit of measure', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->embalagens()->byUnitOfMeasure('CX');

        expect($builder->toQueryString())->toContain('unidadeMedida.eq=CX');
    });

    it('can filter by EAN', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->embalagens()->byEan('7891234567890');

        expect($builder->toQueryString())->toContain('ean.eq=7891234567890');
    });

    it('can filter for purchase and sale', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->embalagens()->forPurchaseAndSale();

        expect($builder->toQueryString())->toContain('tipoEmbalagem.eq=0');
    });

    it('can filter for purchase only', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->embalagens()->forPurchaseOnly();

        expect($builder->toQueryString())->toContain('tipoEmbalagem.eq=1');
    });

    it('can filter for sale only', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->embalagens()->forSaleOnly();

        expect($builder->toQueryString())->toContain('tipoEmbalagem.eq=2');
    });

    it('can add packaging using helper method', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.test.uniplus.com'),
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/embalagens' => Http::response([
                'produto' => 'PROD001',
                'unidadeMedida' => 'CX',
            ], 201),
        ]);

        $manager = app(UniplusManager::class);
        $result = $manager->embalagens()->addPackaging([
            'produto' => 'PROD001',
            'unidadeMedida' => 'CX',
        ]);

        expect($result['unidadeMedida'])->toBe('CX');
    });

    it('can update packaging using helper method', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.test.uniplus.com'),
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/embalagens' => Http::response([
                'produto' => 'PROD001',
                'unidadeMedida' => 'CX',
                'fatorConversao' => 12,
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $result = $manager->embalagens()->updatePackaging([
            'produto' => 'PROD001',
            'fatorConversao' => 12,
        ]);

        expect($result['fatorConversao'])->toBe(12);
    });

    it('throws exception on delete', function () {
        $manager = app(UniplusManager::class);
        $manager->embalagens()->delete('1');
    })->throws(UniplusException::class, 'Delete operation is not supported for Embalagens');
});

describe('RegistroProducao Resource - Extended', function () {
    it('can filter by branch code', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->registroProducao()->byBranch('001');

        expect($builder->toQueryString())->toContain('codigoFilial.eq=001');
    });

    it('can filter by product code', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->registroProducao()->byProductCode('PROD001');

        expect($builder->toQueryString())->toContain('codigoProduto.eq=PROD001');
    });

    it('can filter by stock location', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->registroProducao()->byStockLocation('LOC001');

        expect($builder->toQueryString())->toContain('codigoLocalEstoque.eq=LOC001');
    });

    it('can filter by user', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->registroProducao()->byUser(10);

        expect($builder->toQueryString())->toContain('idUsuario.eq=10');
    });

    it('can create a production record using helper method', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.test.uniplus.com'),
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/registro-producao' => Http::response([
                'codigo' => '12345',
                'descricao' => 'Produção do dia',
            ], 201),
        ]);

        $manager = app(UniplusManager::class);
        $result = $manager->registroProducao()->createRecord([
            'descricao' => 'Produção do dia',
            'itens' => [
                ['idProduto' => 1, 'quantidade' => 100],
            ],
        ]);

        expect($result['codigo'])->toBe('12345');
    });

    it('throws exception on update', function () {
        $manager = app(UniplusManager::class);
        $manager->registroProducao()->update(['descricao' => 'Test']);
    })->throws(UniplusException::class, 'Update operation is not supported for RegistroProducao');

    it('throws exception on delete', function () {
        $manager = app(UniplusManager::class);
        $manager->registroProducao()->delete('1');
    })->throws(UniplusException::class, 'Delete operation is not supported for RegistroProducao');
});

describe('Variacao Resource - Extended', function () {
    it('has correct type constants', function () {
        expect(Variacao::TIPO_LINHA)->toBe(0)
            ->and(Variacao::TIPO_COLUNA)->toBe(1);
    });

    it('can filter by type', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->variacoes()->byType(Variacao::TIPO_COLUNA);

        expect($builder->toQueryString())->toContain('tipoRegistro.eq=1');
    });

    it('can filter column variations', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->variacoes()->columns();

        expect($builder->toQueryString())->toContain('tipoRegistro.eq=1');
    });

    it('can filter by description', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->variacoes()->byDescription('Vermelho');

        expect($builder->toQueryString())->toContain('descricao.eq=Vermelho');
    });

    it('can add variation using helper method', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.test.uniplus.com'),
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/variacoes' => Http::response([
                'variacao' => 'VAR001',
                'descricao' => 'Vermelho',
            ], 201),
        ]);

        $manager = app(UniplusManager::class);
        $result = $manager->variacoes()->addVariation([
            'produto' => 'PROD001',
            'codigoGrade' => 'COR',
            'descricao' => 'Vermelho',
        ]);

        expect($result['descricao'])->toBe('Vermelho');
    });

    it('can update variation using helper method', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.test.uniplus.com'),
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/variacoes' => Http::response([
                'variacao' => 'VAR001',
                'descricao' => 'Azul',
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $result = $manager->variacoes()->updateVariation([
            'variacao' => 'VAR001',
            'descricao' => 'Azul',
        ]);

        expect($result['descricao'])->toBe('Azul');
    });

    it('throws exception on delete', function () {
        $manager = app(UniplusManager::class);
        $manager->variacoes()->delete('VAR001');
    })->throws(UniplusException::class, 'Delete operation is not supported for Variacoes');
});

describe('ItemNotaEntrada Resource - Extended', function () {
    it('has correct item type constants', function () {
        expect(ItemNotaEntrada::TIPO_PRODUTO)->toBe('P')
            ->and(ItemNotaEntrada::TIPO_SERVICO)->toBe('S');
    });

    it('has correct status constants', function () {
        expect(ItemNotaEntrada::STATUS_NORMAL)->toBe(0)
            ->and(ItemNotaEntrada::STATUS_NFE_AUTORIZADA)->toBe(3)
            ->and(ItemNotaEntrada::STATUS_NFE_REJEITADA)->toBe(4)
            ->and(ItemNotaEntrada::STATUS_NFE_NEGADA)->toBe(5);
    });

    it('can filter by invoice ID', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->itemNotaEntrada()->byInvoice(12345);

        expect($builder->toQueryString())->toContain('idNotaFiscal.eq=12345');
    });

    it('can filter by invoice number', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->itemNotaEntrada()->byInvoiceNumber('123456');

        expect($builder->toQueryString())->toContain('numeroNotaFiscal.eq=123456');
    });

    it('can filter by item type', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->itemNotaEntrada()->byItemType('P');

        expect($builder->toQueryString())->toContain('tipoItem.eq=P');
    });

    it('can filter products only', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->itemNotaEntrada()->products();

        expect($builder->toQueryString())->toContain('tipoItem.eq=P');
    });

    it('can filter services only', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->itemNotaEntrada()->services();

        expect($builder->toQueryString())->toContain('tipoItem.eq=S');
    });

    it('can filter by CFOP', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->itemNotaEntrada()->byCfop('5102');

        expect($builder->toQueryString())->toContain('cfopItem.eq=5102');
    });

    it('can filter by date range', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->itemNotaEntrada()->byDateRange('2024-01-01', '2024-12-31');

        $query = $builder->toQueryString();
        expect($query)->toContain('dataEntrada.ge=2024-01-01')
            ->and($query)->toContain('dataEntrada.le=2024-12-31');
    });

    it('can filter by status', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->itemNotaEntrada()->byStatus(ItemNotaEntrada::STATUS_NFE_AUTORIZADA);

        expect($builder->toQueryString())->toContain('status.eq=3');
    });

    it('can filter authorized invoices', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->itemNotaEntrada()->authorized();

        expect($builder->toQueryString())->toContain('status.eq=3');
    });

    it('can filter devolutions', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->itemNotaEntrada()->devolutions();

        expect($builder->toQueryString())->toContain('notaFiscalDevolucao.eq=S');
    });

    it('can filter not devolutions', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->itemNotaEntrada()->notDevolutions();

        expect($builder->toQueryString())->toContain('notaFiscalDevolucao.eq=N');
    });
});

describe('ItemNotaEntradaCompra Resource - Extended', function () {
    it('has correct item type constants', function () {
        expect(ItemNotaEntradaCompra::TIPO_PRODUTO)->toBe('P')
            ->and(ItemNotaEntradaCompra::TIPO_SERVICO)->toBe('S');
    });

    it('can filter by invoice number', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->itemNotaEntradaCompra()->byInvoiceNumber('123456');

        expect($builder->toQueryString())->toContain('numeroNotaFiscal.eq=123456');
    });

    it('can filter by product', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->itemNotaEntradaCompra()->byProduct('PROD001');

        expect($builder->toQueryString())->toContain('produtoCodigo.eq=PROD001');
    });

    it('can filter by supplier', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->itemNotaEntradaCompra()->bySupplier('FORN001');

        expect($builder->toQueryString())->toContain('fornecedorCodigo.eq=FORN001');
    });

    it('can filter by branch', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->itemNotaEntradaCompra()->byBranch('001');

        expect($builder->toQueryString())->toContain('filialCodigo.eq=001');
    });

    it('can filter products only', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->itemNotaEntradaCompra()->products();

        expect($builder->toQueryString())->toContain('tipoItem.eq=P');
    });

    it('can filter services only', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->itemNotaEntradaCompra()->services();

        expect($builder->toQueryString())->toContain('tipoItem.eq=S');
    });

    it('can filter by CFOP', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->itemNotaEntradaCompra()->byCfop('1102');

        expect($builder->toQueryString())->toContain('cfopItem.eq=1102');
    });

    it('can filter by date range', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->itemNotaEntradaCompra()->byDateRange('2024-01-01', '2024-12-31');

        $query = $builder->toQueryString();
        expect($query)->toContain('dataEntrada.ge=2024-01-01')
            ->and($query)->toContain('dataEntrada.le=2024-12-31');
    });

    it('can filter by status', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->itemNotaEntradaCompra()->byStatus(3);

        expect($builder->toQueryString())->toContain('status.eq=3');
    });

    it('can filter devolutions', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->itemNotaEntradaCompra()->devolutions();

        expect($builder->toQueryString())->toContain('notaFiscalDevolucao.eq=S');
    });

    it('can filter not devolutions', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->itemNotaEntradaCompra()->notDevolutions();

        expect($builder->toQueryString())->toContain('notaFiscalDevolucao.eq=N');
    });
});

describe('MovimentacaoEstoque Resource - Extended', function () {
    it('can filter by branch', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->movimentacaoEstoque()->byBranch('001');

        expect($builder->toQueryString())->toContain('filial.eq=001');
    });

    it('can filter by stock location', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->movimentacaoEstoque()->byStockLocation('LOC001');

        expect($builder->toQueryString())->toContain('localEstoque.eq=LOC001');
    });

    it('can filter by variation', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->movimentacaoEstoque()->byVariation('VAR001');

        expect($builder->toQueryString())->toContain('variacao.eq=VAR001');
    });

    it('can filter by type', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->movimentacaoEstoque()->byType('E');

        expect($builder->toQueryString())->toContain('tipoMovimentacao.eq=E');
    });

    it('can filter modified since timestamp', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->movimentacaoEstoque()->modifiedSince(1704067200000);

        expect($builder->toQueryString())->toContain('currenttimemillis.ge=1704067200000');
    });

    it('can filter by date range', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->movimentacaoEstoque()->byDateRange('2024-01-01', '2024-12-31');

        $query = $builder->toQueryString();
        expect($query)->toContain('dataMovimentacao.ge=2024-01-01')
            ->and($query)->toContain('dataMovimentacao.le=2024-12-31');
    });

    it('can filter by reason', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->movimentacaoEstoque()->byReason('VENDA');

        expect($builder->toQueryString())->toContain('motivo.eq=VENDA');
    });

    it('can filter by nature operation', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->movimentacaoEstoque()->byNatureOperation('5102');

        expect($builder->toQueryString())->toContain('naturezaOperacao.eq=5102');
    });
});

describe('TipoDocumentoFinanceiro Resource - Extended', function () {
    it('has all action constants', function () {
        expect(TipoDocumentoFinanceiro::ACTION_CHECKING_ACCOUNT)->toBe(4)
            ->and(TipoDocumentoFinanceiro::ACTION_CHECKS)->toBe(10)
            ->and(TipoDocumentoFinanceiro::ACTION_THIRD_PARTY_CHECKS)->toBe(11)
            ->and(TipoDocumentoFinanceiro::ACTION_CHECK_DEPOSIT)->toBe(12)
            ->and(TipoDocumentoFinanceiro::ACTION_ENDORSEMENT_EXCHANGE)->toBe(13)
            ->and(TipoDocumentoFinanceiro::ACTION_REFUND)->toBe(14)
            ->and(TipoDocumentoFinanceiro::ACTION_DISCHARGE)->toBe(15)
            ->and(TipoDocumentoFinanceiro::ACTION_CUSTOMER_ADVANCE)->toBe(16)
            ->and(TipoDocumentoFinanceiro::ACTION_ADVANCE_PAYMENT)->toBe(17)
            ->and(TipoDocumentoFinanceiro::ACTION_PREPAID)->toBe(8)
            ->and(TipoDocumentoFinanceiro::ACTION_PAY_PREPAID)->toBe(19)
            ->and(TipoDocumentoFinanceiro::ACTION_DIGITAL_WALLET)->toBe(22)
            ->and(TipoDocumentoFinanceiro::ACTION_OWN_CREDIT_CARD)->toBe(24)
            ->and(TipoDocumentoFinanceiro::ACTION_OWN_DEBIT_CARD)->toBe(25);
    });

    it('has all usage type constants', function () {
        expect(TipoDocumentoFinanceiro::USE_TYPE_BOTH)->toBe(0)
            ->and(TipoDocumentoFinanceiro::USE_TYPE_REGISTRATION)->toBe(1)
            ->and(TipoDocumentoFinanceiro::USE_TYPE_PAYMENT_METHOD)->toBe(2);
    });

    it('has all usage location constants', function () {
        expect(TipoDocumentoFinanceiro::USE_LOCATION_BOTH)->toBe(0)
            ->and(TipoDocumentoFinanceiro::USE_LOCATION_RECEIVABLE)->toBe(1)
            ->and(TipoDocumentoFinanceiro::USE_LOCATION_PAYABLE)->toBe(2);
    });

    it('can filter by usage type', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->tipoDocumentoFinanceiro()->byUsageType(TipoDocumentoFinanceiro::USE_TYPE_PAYMENT_METHOD);

        expect($builder->toQueryString())->toContain('tipoUso.eq=2');
    });

    it('can filter by usage location', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->tipoDocumentoFinanceiro()->byUsageLocation(TipoDocumentoFinanceiro::USE_LOCATION_PAYABLE);

        expect($builder->toQueryString())->toContain('localUso.eq=2');
    });

    it('can filter inactive types', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->tipoDocumentoFinanceiro()->inactive();

        expect($builder->toQueryString())->toContain('inativo.eq=1');
    });

    it('can filter types that generate commission', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->tipoDocumentoFinanceiro()->generatesCommission();

        expect($builder->toQueryString())->toContain('baixaGeraComissao.eq=1');
    });

    it('can filter types that allow boleto', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->tipoDocumentoFinanceiro()->allowsBoleto();

        expect($builder->toQueryString())->toContain('permiteGerarBoleto.eq=1');
    });

    it('can filter types used in negotiation', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->tipoDocumentoFinanceiro()->usedInNegotiation();

        expect($builder->toQueryString())->toContain('utilizadoEmNegociacao.eq=1');
    });

    it('can filter mobile types', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->tipoDocumentoFinanceiro()->mobile();

        expect($builder->toQueryString())->toContain('enviaMobile.eq=1');
    });

    it('can filter ecommerce types', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->tipoDocumentoFinanceiro()->ecommerce();

        expect($builder->toQueryString())->toContain('enviaEcommerce.eq=1');
    });

    it('can filter payables', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->tipoDocumentoFinanceiro()->payables();

        expect($builder->toQueryString())->toContain('localUso.eq=2');
    });

    it('can filter credit card types', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->tipoDocumentoFinanceiro()->creditCard();

        expect($builder->toQueryString())->toContain('acao.eq=20');
    });

    it('can filter debit card types', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->tipoDocumentoFinanceiro()->debitCard();

        expect($builder->toQueryString())->toContain('acao.eq=21');
    });

    it('can filter digital wallet types', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->tipoDocumentoFinanceiro()->digitalWallet();

        expect($builder->toQueryString())->toContain('acao.eq=22');
    });
});

describe('SaldoEstoqueVariacao Resource - Extended', function () {
    it('can filter by product', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->saldoEstoqueVariacao()->byProduct('PROD001');

        expect($builder->toQueryString())->toContain('produto.eq=PROD001');
    });

    it('can filter by branch', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->saldoEstoqueVariacao()->byBranch('001');

        expect($builder->toQueryString())->toContain('filial.eq=001');
    });

    it('can filter by variation', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->saldoEstoqueVariacao()->byVariation('VAR001');

        expect($builder->toQueryString())->toContain('variacao.eq=VAR001');
    });

    it('can filter by stock location', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->saldoEstoqueVariacao()->byStockLocation('LOC001');

        expect($builder->toQueryString())->toContain('localEstoque.eq=LOC001');
    });

    it('can filter without stock', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->saldoEstoqueVariacao()->withoutStock();

        expect($builder->toQueryString())->toContain('saldo.le=0');
    });

    it('can filter above quantity', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->saldoEstoqueVariacao()->aboveQuantity(100);

        expect($builder->toQueryString())->toContain('saldo.gt=100');
    });
});

describe('ContaGourmet Resource - Extended', function () {
    it('can filter by branch', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->contaGourmet()->byBranch('001');

        expect($builder->toQueryString())->toContain('codigoFilial.eq=001');
    });

    it('can filter by type', function () {
        $manager = app(UniplusManager::class);
        $builder = $manager->contaGourmet()->byType('MESA');

        expect($builder->toQueryString())->toContain('tipo.eq=MESA');
    });

    it('can find a tab account', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.test.uniplus.com'),
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/gourmet/conta*' => Http::response([
                'numero' => 25,
                'tipo' => 'COMANDA',
                'itens' => [],
            ]),
        ]);

        $manager = app(UniplusManager::class);
        $conta = $manager->contaGourmet()->findTab(25, '1');

        expect($conta['numero'])->toBe(25)
            ->and($conta['tipo'])->toBe('COMANDA');
    });

    it('can create a table account', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.test.uniplus.com'),
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/gourmet/conta' => Http::response([
                'numero' => 10,
                'tipo' => 'MESA',
                'codigoFilial' => '001',
            ], 201),
        ]);

        $manager = app(UniplusManager::class);
        $items = [
            $manager->contaGourmet()->buildItem('PROD001', 'X-Burger', 1, 25.00),
        ];
        $result = $manager->contaGourmet()->createTable(10, '001', $items);

        expect($result['numero'])->toBe(10)
            ->and($result['tipo'])->toBe('MESA');
    });

    it('can create a tab account', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.test.uniplus.com'),
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/gourmet/conta' => Http::response([
                'numero' => 25,
                'tipo' => 'COMANDA',
                'codigoFilial' => '001',
            ], 201),
        ]);

        $manager = app(UniplusManager::class);
        $items = [
            $manager->contaGourmet()->buildItem('PROD001', 'X-Burger', 1, 25.00),
        ];
        $result = $manager->contaGourmet()->createTab(25, '001', $items);

        expect($result['numero'])->toBe(25)
            ->and($result['tipo'])->toBe('COMANDA');
    });

    it('can add items to an existing account', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.test.uniplus.com'),
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/gourmet/conta' => Http::response([
                'numero' => 10,
                'tipo' => 'MESA',
                'itens' => [
                    ['codigoProduto' => 'PROD001'],
                    ['codigoProduto' => 'PROD002'],
                ],
            ], 201),
        ]);

        $manager = app(UniplusManager::class);
        $newItems = [
            $manager->contaGourmet()->buildItem('PROD002', 'Refrigerante', 2, 8.00),
        ];
        $result = $manager->contaGourmet()->addItems(10, 'MESA', '001', $newItems);

        expect($result['numero'])->toBe(10);
    });

    it('can build an item with discount', function () {
        $manager = app(UniplusManager::class);
        $item = $manager->contaGourmet()->buildItem('PROD001', 'X-Burger', 2.0, 15.0, 'UN', [
            'valorDescontoSubtotal' => 5.00,
        ]);

        expect($item['codigoProduto'])->toBe('PROD001')
            ->and($item['valorTotal'])->toBe('25.000')
            ->and($item['valorDescontoSubtotal'])->toBe(5.00);
    });

    it('can build an optional with custom values', function () {
        $manager = app(UniplusManager::class);
        $optional = $manager->contaGourmet()->buildOptional('COM', 'QUEIJO', 'Queijo Extra', 2.0, 3.50);

        expect($optional['tipo'])->toBe('COM')
            ->and($optional['quantidade'])->toBe('2.000')
            ->and($optional['valorTotal'])->toBe('7.00');
    });

    it('can use createOrUpdate as alias for create', function () {
        Http::fake([
            '*test-router.example.com/*' => Http::response('https://api.test.uniplus.com'),
            '*/oauth/token' => Http::response([
                'access_token' => 'test-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            '*/public-api/v1/gourmet/conta' => Http::response([
                'numero' => 10,
                'tipo' => 'MESA',
            ], 201),
        ]);

        $manager = app(UniplusManager::class);
        $result = $manager->contaGourmet()->createOrUpdate([
            'numero' => 10,
            'tipo' => 'MESA',
            'codigoFilial' => '001',
            'itens' => [],
        ]);

        expect($result['numero'])->toBe(10);
    });

    it('delete returns false', function () {
        $manager = app(UniplusManager::class);
        $result = $manager->contaGourmet()->delete('10');

        expect($result)->toBeFalse();
    });
});
