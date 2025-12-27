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
