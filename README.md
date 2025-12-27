# Uniplus Laravel

[![Tests](https://github.com/rfl-designer/uniplus-laravel/actions/workflows/tests.yml/badge.svg)](https://github.com/rfl-designer/uniplus-laravel/actions/workflows/tests.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg)](https://phpstan.org/)
[![Laravel](https://img.shields.io/badge/Laravel-11.x%20%7C%2012.x-orange.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-blue.svg)](https://php.net)

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

#### Operações em Lote (Bulk)

```php
use Uniplus\Facades\Uniplus;

// Atualizar preços de múltiplos produtos de uma vez
Uniplus::produtos()->updatePrecos([
    ['codigo' => '001', 'preco' => 99.90],
    ['codigo' => '002', 'preco' => 149.90],
]);

// Atualizar preços com preços por filial e pautas de preço
Uniplus::produtos()->updatePrecos([
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

// Criar múltiplos produtos de uma vez
Uniplus::produtos()->createMany([
    ['nome' => 'Produto 1', 'preco' => 99.90, 'unidadeMedida' => 'UN'],
    ['nome' => 'Produto 2', 'preco' => 149.90, 'unidadeMedida' => 'UN'],
]);
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

### Movimentação de Estoque

```php
use Uniplus\Facades\Uniplus;

// Todas as movimentações
$movimentacoes = Uniplus::movimentacaoEstoque()->all();

// Apenas entradas
$entradas = Uniplus::movimentacaoEstoque()->entries()->get();

// Apenas saídas
$saidas = Uniplus::movimentacaoEstoque()->exits()->get();

// Por produto
$movs = Uniplus::movimentacaoEstoque()
    ->byProduct('PROD001')
    ->byDateRange('2024-01-01', '2024-12-31')
    ->get();
```

### Saldo de Estoque por Variação

```php
use Uniplus\Facades\Uniplus;

// Saldo por produto e filial
$saldo = Uniplus::saldoEstoqueVariacao()
    ->byProductAndBranch('PROD001', '1')
    ->get();

// Produtos com saldo
$comEstoque = Uniplus::saldoEstoqueVariacao()
    ->withStock()
    ->get();

// Produtos abaixo do mínimo
$baixoEstoque = Uniplus::saldoEstoqueVariacao()
    ->belowMinimum(10)
    ->get();
```

### Ordens de Serviço

```php
use Uniplus\Facades\Uniplus;

// Listar todas
$ordens = Uniplus::ordemServico()->all();

// Apenas abertas
$abertas = Uniplus::ordemServico()->open()->get();

// Em execução
$emExecucao = Uniplus::ordemServico()->inProgress()->get();

// Por cliente
$ordens = Uniplus::ordemServico()
    ->byClient('CLI001')
    ->get();
```

### Tipos de Documento Financeiro

```php
use Uniplus\Facades\Uniplus;

// Todos os tipos
$tipos = Uniplus::tipoDocumentoFinanceiro()->all();

// Apenas ativos
$ativos = Uniplus::tipoDocumentoFinanceiro()->active()->get();

// Tipos PIX
$pix = Uniplus::tipoDocumentoFinanceiro()->pix()->get();

// Cartão de crédito
$credito = Uniplus::tipoDocumentoFinanceiro()->creditCard()->get();

// A receber
$receber = Uniplus::tipoDocumentoFinanceiro()->receivables()->get();
```

### Códigos EAN

```php
use Uniplus\Facades\Uniplus;

// Por produto
$eans = Uniplus::eans()->byProduct('PROD001')->get();

// Por código de barras
$ean = Uniplus::eans()->byBarcode('7891234567890')->first();

// Criar EAN
Uniplus::eans()->create([
    'codigoProduto' => 'PROD001',
    'ean' => '7891234567890',
]);

// Deletar EAN
Uniplus::eans()->delete('7891234567890');
```

### Embalagens

```php
use Uniplus\Facades\Uniplus;

// Por produto
$embalagens = Uniplus::embalagens()->byProduct('PROD001')->get();

// Por tipo
$caixas = Uniplus::embalagens()->byType(1)->get();

// Criar embalagem
Uniplus::embalagens()->create([
    'codigoProduto' => 'PROD001',
    'tipoEmbalagem' => 1,
    'quantidade' => 12,
]);
```

### Variações de Produtos

```php
use Uniplus\Facades\Uniplus;

// Por produto
$variacoes = Uniplus::variacoes()->byProduct('PROD001')->get();

// Por grade
$cores = Uniplus::variacoes()->byGrid('COR')->get();

// Variações de linha
$linhas = Uniplus::variacoes()->rows()->get();

// Variações de coluna
$colunas = Uniplus::variacoes()->columns()->get();
```

### Registro de Produção

```php
use Uniplus\Facades\Uniplus;

// Por filial
$registros = Uniplus::registroProducao()
    ->byBranch('1')
    ->get();

// Por produto
$registros = Uniplus::registroProducao()
    ->byProduct(123)
    ->get();

// Criar registro
Uniplus::registroProducao()->createRecord([
    'descricao' => 'Produção do dia',
    'itens' => [
        ['idProduto' => 123, 'quantidade' => 100],
    ],
]);
```

### Conta Gourmet

```php
use Uniplus\Facades\Uniplus;

// Buscar mesa
$mesa = Uniplus::contaGourmet()->findTable(10, '1');

// Buscar comanda
$comanda = Uniplus::contaGourmet()->findTab(25, '1');

// Listar mesas
$mesas = Uniplus::contaGourmet()->tables()->get();

// Listar comandas
$comandas = Uniplus::contaGourmet()->tabs()->get();

// Construir item para envio
$item = Uniplus::contaGourmet()->buildItem('PROD001', 'X-Burger', 2, 15.00);

// Construir adicional
$adicional = Uniplus::contaGourmet()->buildAddon('BACON', 'Bacon Extra', 1, 5.00);

// Construir remoção
$remocao = Uniplus::contaGourmet()->buildRemoval('CEBOLA', 'Cebola');
```

### Commons (70+ Tabelas Auxiliares)

O pacote oferece acesso a mais de 70 tabelas auxiliares através do `CommonsFactory`:

```php
use Uniplus\Facades\Uniplus;

// Bancos
$bancos = Uniplus::commons()->banco()->all();

// Cidades
$cidades = Uniplus::commons()->cidade()
    ->where('idestado', 1)
    ->get();

// Estados
$estados = Uniplus::commons()->estado()->all();

// Filiais
$filiais = Uniplus::commons()->filial()->all();

// Tipos de pedido
$tipos = Uniplus::commons()->tipoPedido()->all();

// Formas de pagamento
$formas = Uniplus::commons()->formaPagamento()->all();

// Buscar por ID
$banco = Uniplus::commons()->banco()->find(1);

// Acesso genérico (para tabelas não mapeadas)
$dados = Uniplus::commons()->table('tipopedido')->all();

// Verificar tabelas disponíveis
$tabelas = \Uniplus\Resources\Commons\CommonsFactory::getAvailableTables();

// Verificar se tabela existe
$existe = \Uniplus\Resources\Commons\CommonsFactory::isValidTable('banco');
```

**Tabelas disponíveis incluem:** banco, cidade, estado, pais, filial, formaPagamento, tipoPedido, grupoItem, subgrupoItem, tipoPessoa, estadoCivil, cfop, ncm, cst, origem, naturezaOperacao, tipoMensalidade, e muitas outras.

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
