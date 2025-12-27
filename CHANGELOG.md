# Changelog

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [1.3.0] - 2025-12-27

### Corrigido

- **Mapeamentos de endpoints Commons** corrigidos para corresponder à API real:
  - `motivoAcertoEstoque()` agora chama `/commons/tipodocumentoestoque` (era `/commons/motivoacertoestoque`)
  - `motivoCancelamentoOperacao()` agora chama `/commons/motivocancelamentooperacao` (renomeado de `motivoCancelamentoPdv`)
  - `motivoSangriaSuprimento()` agora chama `/commons/motivosangriasuprimento` (renomeado de `motivoSangria`)
  - `cfop()` agora chama `/commons/cfop` (renomeado de `naturezaOperacao`)
  - `desmontagemNotaFiscal()` agora chama `/commons/desmontagemnotafiscal` (renomeado de `notaFiscalDesmontagem`)
  - `tipoEntidadeOperacaoFiscal()` agora chama `/commons/tipoentidadeoperacaofiscal` (renomeado de `tipoEntidadeOperacao`)
  - `tipoInformacaoAdicionalApur()` agora chama `/commons/tipoinformacaoadicionalapur` (renomeado de `tipoInformacaoAdicional`)

### Adicionado

- **7 novos testes** para validar os mapeamentos corrigidos

## [1.2.0] - 2025-12-27

### Adicionado

- **Operações em Lote para Produtos:**
  - `Produto::updatePrecos()` - Atualização de preços em massa para múltiplos produtos
  - `Produto::createMany()` - Criação de múltiplos produtos de uma vez
  - Suporte a preços por filial e pautas de preço no `updatePrecos()`
  - Validação de array vazio com `InvalidArgumentException`

- **5 novos testes** para os métodos bulk (total: 377 testes)

## [1.1.0] - 2025-12-27

### Adicionado

- **Novos Recursos da API (14 novos):**
  - `GrupoShop` - Categorias de e-commerce (somente leitura)
  - `OrdemServico` - Ordens de serviço (somente leitura)
  - `Ean` - Códigos de barras EAN (GET, POST, DELETE)
  - `Embalagem` - Embalagens de produtos (GET, POST, PUT)
  - `RegistroProducao` - Registros de produção (GET, POST)
  - `Variacao` - Variações de produtos/grades (GET, POST, PUT)
  - `ItemNotaEntrada` - Itens de notas de entrada (somente leitura)
  - `ItemNotaEntradaCompra` - Itens de notas de compra (somente leitura)
  - `MovimentacaoEstoque` - Movimentações de estoque v2 (somente leitura)
  - `TipoDocumentoFinanceiro` - Tipos de documentos financeiros (somente leitura)
  - `SaldoEstoqueVariacao` - Saldo de estoque por variação v2 (somente leitura)
  - `ContaGourmet` - Contas do módulo Gourmet (GET, POST)

- **Commons Factory** com acesso a 70+ tabelas auxiliares:
  - Acesso via `Uniplus::commons()->banco()`, `->cidade()`, `->estado()`, etc.
  - Método genérico `->table('nome')` para tabelas não mapeadas
  - Validação de tabelas disponíveis
  - Somente leitura (GET)

- **Classes Base:**
  - `ReadOnlyResource` - Classe base para recursos somente GET
  - `CommonsResource` - Classe base para recursos Commons
  - `CommonsFactory` - Factory com métodos mágicos para 70+ tabelas

- **85 novos testes** para os novos recursos (total: 258 testes)

## [1.0.0] - 2025-12-27

### Adicionado

- **Autenticação OAuth2** com cache automático de tokens (58 minutos)
- **Recursos da API:**
  - `Produto` - CRUD completo de produtos
  - `Entidade` - Clientes, fornecedores e transportadoras
  - `Dav` - Pré-vendas, orçamentos e pedidos de venda
  - `SaldoEstoque` - Consulta e atualização de saldos
  - `Venda` - Consulta de vendas finalizadas (somente leitura)
  - `VendaItem` - Itens das vendas (somente leitura)
- **Query Builder** fluente com suporte a:
  - Filtros com operadores (`=`, `!=`, `>`, `>=`, `<`, `<=`)
  - Paginação (`limit`, `offset`)
  - Ordenação
- **Multi-tenant** - Suporte a múltiplas conexões simultâneas
- **Resolução dinâmica de URLs** via serviço de roteamento da Uniplus
- **Sistema de eventos:**
  - `TokenRefreshed` - Token obtido/renovado
  - `RequestSending` - Antes de enviar requisição
  - `RequestSent` - Após resposta bem sucedida
  - `RequestFailed` - Após resposta com erro
- **Exceções tipadas:**
  - `AuthenticationException` - Erros de autenticação
  - `ValidationException` - Erros de validação
  - `NotFoundException` - Recurso não encontrado
  - `ConnectionException` - Erros de conexão
- **FakeClient** para testes com asserções
- **PHPStan Level 9** - Análise estática completa
- **173 testes automatizados** com Pest

### Suporte

- PHP 8.3+
- Laravel 11.x e 12.x

[1.3.0]: https://github.com/rfl-designer/uniplus-laravel/releases/tag/v1.3.0
[1.2.0]: https://github.com/rfl-designer/uniplus-laravel/releases/tag/v1.2.0
[1.1.0]: https://github.com/rfl-designer/uniplus-laravel/releases/tag/v1.1.0
[1.0.0]: https://github.com/rfl-designer/uniplus-laravel/releases/tag/v1.0.0
