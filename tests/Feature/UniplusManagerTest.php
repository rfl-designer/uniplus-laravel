<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Uniplus\Exceptions\ConnectionException;
use Uniplus\Resources\Dav;
use Uniplus\Resources\Entidade;
use Uniplus\Resources\Produto;
use Uniplus\Resources\SaldoEstoque;
use Uniplus\Resources\Venda;
use Uniplus\Resources\VendaItem;
use Uniplus\Testing\FakeClient;
use Uniplus\Uniplus;
use Uniplus\UniplusManager;

beforeEach(function () {
    config([
        'uniplus.default' => 'default',
        'uniplus.connections.default' => [
            'account' => 'default-account',
            'authorization_code' => base64_encode('default:secret'),
            'user_id' => 1,
            'branch_id' => 1,
        ],
        'uniplus.connections.tenant1' => [
            'account' => 'tenant1-account',
            'authorization_code' => base64_encode('tenant1:secret'),
            'user_id' => 10,
            'branch_id' => 5,
        ],
        'uniplus.connections.tenant2' => [
            'account' => 'tenant2-account',
            'authorization_code' => base64_encode('tenant2:secret'),
            'user_id' => 20,
            'branch_id' => 10,
        ],
        'uniplus.cache.enabled' => false,
        'uniplus.logging.enabled' => false,
    ]);

    Http::fake([
        'https://uniplus.info/service-locator/*' => Http::response('https://api.test.uniplus.com'),
    ]);
});

afterEach(function () {
    Http::preventStrayRequests(false);
});

describe('UniplusManager', function () {
    it('returns a Uniplus instance for default connection', function () {
        $manager = app(UniplusManager::class);

        $uniplus = $manager->connection();

        expect($uniplus)->toBeInstanceOf(Uniplus::class);
    });

    it('returns a Uniplus instance for named connection', function () {
        $manager = app(UniplusManager::class);

        $uniplus = $manager->connection('tenant1');

        expect($uniplus)->toBeInstanceOf(Uniplus::class)
            ->and($uniplus->getConnection()->getAccount())->toBe('tenant1-account')
            ->and($uniplus->getConnection()->getUserId())->toBe(10)
            ->and($uniplus->getConnection()->getBranchId())->toBe(5);
    });

    it('caches connection instances', function () {
        $manager = app(UniplusManager::class);

        $uniplus1 = $manager->connection('tenant1');
        $uniplus2 = $manager->connection('tenant1');

        expect($uniplus1)->toBe($uniplus2);
    });

    it('creates separate instances for different connections', function () {
        $manager = app(UniplusManager::class);

        $tenant1 = $manager->connection('tenant1');
        $tenant2 = $manager->connection('tenant2');

        expect($tenant1)->not->toBe($tenant2)
            ->and($tenant1->getConnection()->getAccount())->toBe('tenant1-account')
            ->and($tenant2->getConnection()->getAccount())->toBe('tenant2-account');
    });

    it('throws exception for unconfigured connection', function () {
        $manager = app(UniplusManager::class);

        expect(fn () => $manager->connection('nonexistent'))
            ->toThrow(ConnectionException::class, 'not configured');
    });

    it('returns the default connection name', function () {
        $manager = app(UniplusManager::class);

        expect($manager->getDefaultConnection())->toBe('default');
    });

    it('returns all connection names', function () {
        $manager = app(UniplusManager::class);

        $names = $manager->getConnectionNames();

        expect($names)->toContain('default')
            ->and($names)->toContain('tenant1')
            ->and($names)->toContain('tenant2');
    });

    it('can add a connection dynamically', function () {
        $manager = app(UniplusManager::class);

        $manager->addConnection('dynamic', [
            'account' => 'dynamic-account',
            'authorization_code' => base64_encode('dynamic:secret'),
            'user_id' => 100,
            'branch_id' => 50,
        ]);

        $uniplus = $manager->connection('dynamic');

        expect($uniplus->getConnection()->getAccount())->toBe('dynamic-account')
            ->and($uniplus->getConnection()->getUserId())->toBe(100);
    });

    it('can remove a connection', function () {
        $manager = app(UniplusManager::class);

        // First, create the connection
        $manager->connection('tenant1');

        // Remove it
        $manager->removeConnection('tenant1');

        // It should be recreated on next access (not throw exception)
        $uniplus = $manager->connection('tenant1');
        expect($uniplus)->toBeInstanceOf(Uniplus::class);
    });

    it('can purge all cached connections', function () {
        $manager = app(UniplusManager::class);

        $uniplus1 = $manager->connection('tenant1');
        $uniplus2 = $manager->connection('tenant2');

        $manager->purge();

        $uniplus1New = $manager->connection('tenant1');

        expect($uniplus1)->not->toBe($uniplus1New);
    });

    it('proxies produtos method to default connection', function () {
        $manager = app(UniplusManager::class);

        $produtos = $manager->produtos();

        expect($produtos)->toBeInstanceOf(Produto::class);
    });

    it('proxies entidades method to default connection', function () {
        $manager = app(UniplusManager::class);

        $entidades = $manager->entidades();

        expect($entidades)->toBeInstanceOf(Entidade::class);
    });

    it('proxies davs method to default connection', function () {
        $manager = app(UniplusManager::class);

        $davs = $manager->davs();

        expect($davs)->toBeInstanceOf(Dav::class);
    });

    it('proxies saldoEstoque method to default connection', function () {
        $manager = app(UniplusManager::class);

        $saldoEstoque = $manager->saldoEstoque();

        expect($saldoEstoque)->toBeInstanceOf(SaldoEstoque::class);
    });

    it('proxies vendas method to default connection', function () {
        $manager = app(UniplusManager::class);

        $vendas = $manager->vendas();

        expect($vendas)->toBeInstanceOf(Venda::class);
    });

    it('proxies vendaItens method to default connection', function () {
        $manager = app(UniplusManager::class);

        $vendaItens = $manager->vendaItens();

        expect($vendaItens)->toBeInstanceOf(VendaItem::class);
    });
});

describe('UniplusManager Fake Mode', function () {
    it('can set up fake mode', function () {
        $manager = app(UniplusManager::class);

        $fakeClient = $manager->fake([
            'produtos' => ['id' => 1, 'name' => 'Product 1'],
        ]);

        expect($fakeClient)->toBeInstanceOf(FakeClient::class)
            ->and($manager->isFaking())->toBeTrue();
    });

    it('returns null for getFakeClient when not faking', function () {
        $manager = app(UniplusManager::class);

        expect($manager->isFaking())->toBeFalse()
            ->and($manager->getFakeClient())->toBeNull();
    });

    it('throws exception when asserting without fake mode', function () {
        $manager = app(UniplusManager::class);

        expect(fn () => $manager->assertSent('GET', '/produtos'))
            ->toThrow(RuntimeException::class, 'not in fake mode');
    });
});
