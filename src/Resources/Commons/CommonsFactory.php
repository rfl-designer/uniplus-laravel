<?php

declare(strict_types=1);

namespace Uniplus\Resources\Commons;

use Uniplus\Exceptions\UniplusException;
use Uniplus\Http\Client;

/**
 * Factory for creating Commons resource instances.
 *
 * This factory provides a fluent interface to access any Commons endpoint
 * by its table name, as well as named methods for type-safety.
 *
 * @method CommonsResource administradoraCartao() Get Card Administrators resource
 * @method CommonsResource ajusteApuracaoIcms() Get ICMS Assessment Adjustments resource
 * @method CommonsResource ajusteDocumentoFiscal() Get Tax Document Adjustments resource
 * @method CommonsResource aliquotaEstado() Get State Tax Rates resource
 * @method CommonsResource apuracaoImposto() Get Tax Assessments resource
 * @method CommonsResource banco() Get Banks resource
 * @method CommonsResource bandeiraCartao() Get Card Brands resource
 * @method CommonsResource beneficioFiscal() Get Tax Benefits resource
 * @method CommonsResource categoriaCliente() Get Customer Categories resource
 * @method CommonsResource cep() Get ZIP Codes resource
 * @method CommonsResource cest() Get CEST Codes resource
 * @method CommonsResource centroCusto() Get Cost Centers resource
 * @method CommonsResource cidade() Get Cities resource
 * @method CommonsResource complementoProdutoFilial() Get Branch Product Complements resource
 * @method CommonsResource condicaoPagamento() Get Payment Conditions resource
 * @method CommonsResource creditoEstimulo() Get Stimulus Credits resource
 * @method CommonsResource departamento() Get Departments resource
 * @method CommonsResource desmontagem() Get Product Disassemblies resource
 * @method CommonsResource desmontagemItem() Get Disassembly Items resource
 * @method CommonsResource desmontagemOrigem() Get Disassembly Origins resource
 * @method CommonsResource empresa() Get Companies resource
 * @method CommonsResource enquadramentoIpi() Get IPI Classifications resource
 * @method CommonsResource estado() Get States resource
 * @method CommonsResource familiaProduto() Get Product Families resource
 * @method CommonsResource filial() Get Branches resource
 * @method CommonsResource finalizador() Get Payment Methods resource
 * @method CommonsResource fundoCombatePobreza() Get Poverty Combat Fund resource
 * @method CommonsResource grade() Get Grids resource
 * @method CommonsResource grupoShop() Get E-commerce Categories resource
 * @method CommonsResource hierarquia() Get Product Groups resource
 * @method CommonsResource horarioAtendimento() Get Service Hours resource
 * @method CommonsResource ibpt() Get IBPT resource
 * @method CommonsResource informacaoAdicionalApuracao() Get Additional Assessment Info resource
 * @method CommonsResource localEstoque() Get Stock Locations resource
 * @method CommonsResource localRetirada() Get Pickup Locations resource
 * @method CommonsResource marca() Get Brands resource
 * @method CommonsResource meioTransporte() Get Transport Means resource
 * @method CommonsResource motivoAcertoEstoque() Get Stock Adjustment Reasons resource
 * @method CommonsResource motivoBaixaFinanceiro() Get Financial Write-off Reasons resource
 * @method CommonsResource motivoCancelamentoPdv() Get POS Cancellation Reasons resource
 * @method CommonsResource motivoDesconto() Get Discount Reasons resource
 * @method CommonsResource motivoRebaixa() Get Markdown Reasons resource
 * @method CommonsResource motivoSangria() Get Cash Withdrawal Reasons resource
 * @method CommonsResource naturezaOperacao() Get Operation Natures resource
 * @method CommonsResource notaFiscalDesmontagem() Get Disassembly Invoices resource
 * @method CommonsResource observacaoLancamentoFiscal() Get Tax Entry Notes resource
 * @method CommonsResource pais() Get Countries resource
 * @method CommonsResource pautaPreco() Get Price Lists resource
 * @method CommonsResource pautaPrecoFilial() Get Branch Price Lists resource
 * @method CommonsResource pedidoCompra() Get Purchase Orders resource
 * @method CommonsResource pedidoCompraItem() Get Purchase Order Items resource
 * @method CommonsResource planoContas() Get Chart of Accounts resource
 * @method CommonsResource produtoSimilar() Get Similar Products resource
 * @method CommonsResource promocao() Get Promotions resource
 * @method CommonsResource receitaIcms() Get ICMS Revenue resource
 * @method CommonsResource receitaSemContribuicao() Get Non-Contribution Revenue resource
 * @method CommonsResource regiaoEntidade() Get Entity Regions resource
 * @method CommonsResource subApuracaoIcms() Get ICMS Sub-assessment resource
 * @method CommonsResource tipoCobranca() Get Billing Types resource
 * @method CommonsResource tipoContato() Get Contact Types resource
 * @method CommonsResource tipoCredito() Get Credit Types resource
 * @method CommonsResource tipoEntidadeOperacao() Get Entity Operation Types resource
 * @method CommonsResource tipoHistoricoContato() Get Contact History Types resource
 * @method CommonsResource tipoInformacaoAdicional() Get Additional Info Types resource
 * @method CommonsResource tipoPedido() Get Order Types resource
 * @method CommonsResource unidadeMedida() Get Units of Measure resource
 * @method CommonsResource usuario() Get Users resource
 * @method CommonsResource valorNutricional() Get Nutritional Values resource
 */
class CommonsFactory
{
    protected Client $client;

    /**
     * Map of method names to API table names.
     *
     * @var array<string, string>
     */
    protected static array $tableMap = [
        'administradoraCartao' => 'administradoracartao',
        'ajusteApuracaoIcms' => 'ajusteapuracaoicms',
        'ajusteDocumentoFiscal' => 'ajustedocumentofiscal',
        'aliquotaEstado' => 'aliquotaestado',
        'apuracaoImposto' => 'apuracaoimposto',
        'banco' => 'banco',
        'bandeiraCartao' => 'bandeiracartao',
        'beneficioFiscal' => 'beneficiofiscal',
        'categoriaCliente' => 'categoriacliente',
        'cep' => 'cep',
        'cest' => 'cest',
        'centroCusto' => 'centrocusto',
        'cidade' => 'cidade',
        'complementoProdutoFilial' => 'complementoprodutofilial',
        'condicaoPagamento' => 'condicaopagamento',
        'creditoEstimulo' => 'creditoestimulo',
        'departamento' => 'departamento',
        'desmontagem' => 'desmontagem',
        'desmontagemItem' => 'desmontagemitem',
        'desmontagemOrigem' => 'desmontagemorigem',
        'empresa' => 'empresa',
        'enquadramentoIpi' => 'enquadramentoipi',
        'estado' => 'estado',
        'familiaProduto' => 'familiaproduto',
        'filial' => 'filial',
        'finalizador' => 'finalizador',
        'fundoCombatePobreza' => 'fundocombatepobreza',
        'grade' => 'grade',
        'grupoShop' => 'gruposhop',
        'hierarquia' => 'hierarquia',
        'horarioAtendimento' => 'horarioatendimento',
        'ibpt' => 'ibpt',
        'informacaoAdicionalApuracao' => 'informacaoadicionalapuracao',
        'localEstoque' => 'localestoque',
        'localRetirada' => 'localretirada',
        'marca' => 'marca',
        'meioTransporte' => 'meiotransporte',
        'motivoAcertoEstoque' => 'motivoacertoestoque',
        'motivoBaixaFinanceiro' => 'motivobaixafinanceiro',
        'motivoCancelamentoPdv' => 'motivocancelamentopdv',
        'motivoDesconto' => 'motivodesconto',
        'motivoRebaixa' => 'motivorebaixa',
        'motivoSangria' => 'motivosangria',
        'naturezaOperacao' => 'naturezaoperacao',
        'notaFiscalDesmontagem' => 'notafiscaldesmontagem',
        'observacaoLancamentoFiscal' => 'observacaolancamentofiscal',
        'pais' => 'pais',
        'pautaPreco' => 'pautapreco',
        'pautaPrecoFilial' => 'pautaprecofilial',
        'pedidoCompra' => 'pedidocompra',
        'pedidoCompraItem' => 'pedidocompraitem',
        'planoContas' => 'planocontas',
        'produtoSimilar' => 'produtosimilar',
        'promocao' => 'promocao',
        'receitaIcms' => 'receitaicms',
        'receitaSemContribuicao' => 'receitasemcontribuicao',
        'regiaoEntidade' => 'regiaoentidade',
        'subApuracaoIcms' => 'subapuracaoicms',
        'tipoCobranca' => 'tipocobranca',
        'tipoContato' => 'tipocontato',
        'tipoCredito' => 'tipocredito',
        'tipoEntidadeOperacao' => 'tipoentidadeoperacao',
        'tipoHistoricoContato' => 'tipohistoricocontato',
        'tipoInformacaoAdicional' => 'tipoinformacaoadicional',
        'tipoPedido' => 'tipopedido',
        'unidadeMedida' => 'unidademedida',
        'usuario' => 'usuario',
        'valorNutricional' => 'valornutricional',
    ];

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get a Commons resource by its table name.
     *
     * @param  string  $table  The API table name (e.g., 'banco', 'cidade', 'estado')
     */
    public function table(string $table): CommonsResource
    {
        return new CommonsResource($this->client, $table);
    }

    /**
     * Magic method to create Commons resources via named methods.
     *
     * @param  array<int, mixed>  $arguments
     *
     * @throws UniplusException
     */
    public function __call(string $method, array $arguments): CommonsResource
    {
        if (isset(self::$tableMap[$method])) {
            return $this->table(self::$tableMap[$method]);
        }

        throw new UniplusException("Unknown Commons resource: {$method}");
    }

    /**
     * Get all available table names.
     *
     * @return array<string, string>
     */
    public static function getAvailableTables(): array
    {
        return self::$tableMap;
    }

    /**
     * Check if a table/method name is valid.
     */
    public static function isValidTable(string $name): bool
    {
        return isset(self::$tableMap[$name]) || in_array($name, self::$tableMap, true);
    }

    /**
     * Get the HTTP client.
     */
    public function getClient(): Client
    {
        return $this->client;
    }
}
