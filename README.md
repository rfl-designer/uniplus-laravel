# Uniplus Laravel

[![Tests](https://github.com/rfl-designer/uniplus-laravel/actions/workflows/tests.yml/badge.svg)](https://github.com/rfl-designer/uniplus-laravel/actions/workflows/tests.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg)](https://phpstan.org/)
[![Laravel](https://img.shields.io/badge/Laravel-11.x%20%7C%2012.x-orange.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://php.net)

Pacote Laravel para integração com a API do Uniplus ERP.

## Instalação

### Repositório Privado GitHub

Como este é um pacote privado, adicione o repositório ao seu `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:rfl-designer/uniplus-laravel.git"
        }
    ],
    "require": {
        "rfl-designer/uniplus-laravel": "^1.0"
    }
}
```

> **Nota:** Certifique-se de ter uma chave SSH configurada no GitHub ou use um token de acesso pessoal.

Depois execute:

```bash
composer update
```

### Instalação Local (Desenvolvimento)

Para desenvolvimento local, você pode usar o tipo `path`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/uniplus-laravel"
        }
    ],
    "require": {
        "rfl-designer/uniplus-laravel": "@dev"
    }
}
```

## Configuração

Publique o arquivo de configuração:

```bash
php artisan vendor:publish --tag=uniplus-config
```

Adicione as variáveis de ambiente ao seu `.env`:

```env
UNIPLUS_ACCOUNT=sua_conta
UNIPLUS_AUTH_CODE=seu_codigo_base64
UNIPLUS_USER_ID=1
UNIPLUS_BRANCH_ID=1
```

### Gerando o Código de Autorização

O código de autorização é uma string Base64 no formato `usuario:token`. Para gerar:

```php
$authCode = base64_encode('usuario:seu-token-uuid');
```

## Uso Básico

### Produtos

```php
use Uniplus\Facades\Uniplus;

// Listar todos os produtos
$produtos = Uniplus::produtos()->all();

// Buscar produto por código
$produto = Uniplus::produtos()->find('97');

// Criar produto
$novo = Uniplus::produtos()->create([
    'codigo' => '999000',
    'nome' => 'PRODUTO TESTE',
    'preco' => 10.23,
    'unidadeMedida' => 'UN',
]);

// Atualizar produto
Uniplus::produtos()->update([
    'codigo' => '999000',
    'preco' => 15.50,
]);

// Deletar produto
Uniplus::produtos()->delete('999000');

// Query com filtros
$ativos = Uniplus::produtos()
    ->where('inativo', 0)
    ->where('preco', '>=', 100)
    ->limit(50)
    ->get();

// Produtos ativos
$ativos = Uniplus::produtos()->active()->get();

// Produtos alterados após timestamp
$alterados = Uniplus::produtos()
    ->changedAfter(1616786400000)
    ->get();
```

### Entidades (Clientes, Fornecedores, etc.)

```php
use Uniplus\Facades\Uniplus;

// Listar todas as entidades
$entidades = Uniplus::entidades()->all();

// Apenas clientes
$clientes = Uniplus::entidades()->clients()->get();

// Apenas fornecedores
$fornecedores = Uniplus::entidades()->suppliers()->get();

// Buscar por CPF/CNPJ
$cliente = Uniplus::entidades()
    ->byCpfCnpj('123.456.789-00')
    ->first();

// Criar cliente
$cliente = Uniplus::entidades()->create([
    'codigo' => '10001',
    'nome' => 'João Silva',
    'tipo' => 1, // 1=Cliente
    'cnpjCpf' => '123.456.789-00',
    'email' => 'joao@email.com',
]);
```

### DAVs (Pré-vendas, Orçamentos, Pedidos)

```php
use Uniplus\Facades\Uniplus;

// Listar pré-vendas
$preVendas = Uniplus::davs()->preSales()->get();

// Listar orçamentos
$orcamentos = Uniplus::davs()->quotes()->get();

// Listar pedidos de venda
$pedidos = Uniplus::davs()->salesOrders()->get();

// DAVs abertos
$abertos = Uniplus::davs()->open()->get();

// DAVs por cliente
$davs = Uniplus::davs()->byClient('10001')->get();

// DAVs por período
$davs = Uniplus::davs()
    ->byDateRange('2024-01-01', '2024-12-31')
    ->get();

// Criar DAV
$dav = Uniplus::davs()->create([
    'codigo' => '101',
    'tipoDocumento' => 1, // 1=Pré-venda
    'data' => '2024-01-15',
    'cliente' => '10001',
    'itens' => [
        [
            'produto' => '97',
            'quantidade' => 2,
            'precoUnitario' => 10.50,
        ],
    ],
]);
```

### Saldo de Estoque

```php
use Uniplus\Facades\Uniplus;

// Consultar saldo de um produto
$saldo = Uniplus::saldoEstoque()
    ->getBalance('97', 1); // produto, filial

// Saldo por filial
$saldos = Uniplus::saldoEstoque()
    ->byBranch(1)
    ->get();

// Atualizar saldo
Uniplus::saldoEstoque()->updateBalance([
    'produto' => '97',
    'quantidade' => 200,
    'filial' => '1',
]);
```

### Vendas (Somente Leitura)

```php
use Uniplus\Facades\Uniplus;

// Vendas por período
$vendas = Uniplus::vendas()
    ->byDateRange('2024-01-01', '2024-01-31')
    ->get();

// Vendas por filial
$vendas = Uniplus::vendas()
    ->byBranch(1)
    ->completed()
    ->get();

// Itens de venda
$itens = Uniplus::vendaItens()
    ->byProduct('10084')
    ->byDateRange('2024-01-01', '2024-01-31')
    ->get();
```

## Operadores de Filtro

O pacote suporta os seguintes operadores:

| Operador | Significado | Exemplo |
|----------|-------------|---------|
| `=` ou `eq` | Igual | `where('codigo', '123')` |
| `!=` ou `ne` | Diferente | `where('inativo', '!=', 1)` |
| `>` ou `gt` | Maior que | `where('preco', '>', 100)` |
| `>=` ou `ge` | Maior ou igual | `where('preco', '>=', 100)` |
| `<` ou `lt` | Menor que | `where('preco', '<', 50)` |
| `<=` ou `le` | Menor ou igual | `where('preco', '<=', 50)` |

## Multi-Tenant (Múltiplas Conexões)

Configure múltiplas conexões no `config/uniplus.php`:

```php
'connections' => [
    'default' => [
        'account' => env('UNIPLUS_ACCOUNT'),
        'authorization_code' => env('UNIPLUS_AUTH_CODE'),
        'user_id' => 1,
        'branch_id' => 1,
    ],
    'loja_matriz' => [
        'account' => 'matriz',
        'authorization_code' => '...',
        'user_id' => 1,
        'branch_id' => 1,
    ],
    'loja_filial' => [
        'account' => 'filial',
        'authorization_code' => '...',
        'user_id' => 1,
        'branch_id' => 2,
    ],
],
```

Use conexões específicas:

```php
// Conexão padrão
Uniplus::produtos()->all();

// Conexão específica
Uniplus::connection('loja_matriz')->produtos()->all();
Uniplus::connection('loja_filial')->produtos()->all();

// Adicionar conexão em runtime
Uniplus::addConnection('nova_loja', [
    'account' => 'nova_loja',
    'authorization_code' => '...',
]);
```

## Eventos

O pacote dispara os seguintes eventos:

| Evento | Quando |
|--------|--------|
| `TokenRefreshed` | Após obter/renovar token |
| `RequestSending` | Antes de enviar requisição |
| `RequestSent` | Após resposta bem sucedida |
| `RequestFailed` | Após resposta com erro |

Exemplo de listener:

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    \Uniplus\Events\RequestFailed::class => [
        \App\Listeners\LogUniplusError::class,
    ],
];
```

## Testes

Use o método `fake()` para mockar respostas em testes:

```php
use Uniplus\Facades\Uniplus;

it('lists all products', function () {
    Uniplus::fake([
        'produtos' => [
            ['codigo' => '1', 'nome' => 'Produto A'],
            ['codigo' => '2', 'nome' => 'Produto B'],
        ],
    ]);

    $produtos = Uniplus::produtos()->all();

    expect($produtos)->toHaveCount(2);
    expect($produtos[0]['nome'])->toBe('Produto A');

    Uniplus::assertSent('GET', 'produtos');
});
```

## Cache de Tokens

Os tokens OAuth2 são automaticamente cacheados por 58 minutos (tokens expiram em 60 min).

Configure o cache no `.env`:

```env
UNIPLUS_CACHE_ENABLED=true
UNIPLUS_CACHE_STORE=redis  # opcional, usa default se não informado
```

## Logging

Habilite logging de requisições para debug:

```env
UNIPLUS_LOGGING=true
UNIPLUS_LOG_CHANNEL=stack  # opcional
```

## Licença

MIT
