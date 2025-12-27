# Changelog

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).

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

[1.0.0]: https://github.com/rfl-designer/uniplus-laravel/releases/tag/v1.0.0
