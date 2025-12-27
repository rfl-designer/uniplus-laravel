<?php

declare(strict_types=1);

namespace Uniplus\Facades;

use Illuminate\Support\Facades\Facade;
use Uniplus\Resources\Dav;
use Uniplus\Resources\Entidade;
use Uniplus\Resources\Produto;
use Uniplus\Resources\SaldoEstoque;
use Uniplus\Resources\Venda;
use Uniplus\Resources\VendaItem;
use Uniplus\Uniplus as UniplusClient;

/**
 * @method static UniplusClient connection(?string $name = null)
 * @method static Produto produtos()
 * @method static Entidade entidades()
 * @method static Dav davs()
 * @method static SaldoEstoque saldoEstoque()
 * @method static Venda vendas()
 * @method static VendaItem vendaItens()
 * @method static void fake(array<string, mixed> $responses = [])
 * @method static void assertSent(string $method, string $url)
 * @method static void assertNotSent(string $url)
 * @method static void assertSentCount(int $count)
 *
 * @see \Uniplus\UniplusManager
 */
class Uniplus extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'uniplus';
    }
}
