<?php

declare(strict_types=1);

namespace Uniplus\Resources;

use Uniplus\Query\Builder;

/**
 * Resource for financial document types (Tipo de Documento Financeiro).
 *
 * Endpoint: /public-api/v1/tipo-documento-financeiro
 * Supported methods: GET
 *
 * This endpoint returns financial document types used in the system.
 */
class TipoDocumentoFinanceiro extends ReadOnlyResource
{
    protected string $endpoint = 'public-api/v1/tipo-documento-financeiro';

    protected string $primaryKey = 'codigo';

    /**
     * Action type constants.
     */
    public const ACTION_CASH = 0;

    public const ACTION_RECEIVE = 2;

    public const ACTION_PAY = 3;

    public const ACTION_CHECKING_ACCOUNT = 4;

    public const ACTION_CHECKS = 10;

    public const ACTION_THIRD_PARTY_CHECKS = 11;

    public const ACTION_CHECK_DEPOSIT = 12;

    public const ACTION_ENDORSEMENT_EXCHANGE = 13;

    public const ACTION_REFUND = 14;

    public const ACTION_DISCHARGE = 15;

    public const ACTION_CUSTOMER_ADVANCE = 16;

    public const ACTION_ADVANCE_PAYMENT = 17;

    public const ACTION_PREPAID = 8;

    public const ACTION_PAY_PREPAID = 19;

    public const ACTION_CREDIT_CARD = 20;

    public const ACTION_DEBIT_CARD = 21;

    public const ACTION_DIGITAL_WALLET = 22;

    public const ACTION_PIX = 23;

    public const ACTION_OWN_CREDIT_CARD = 24;

    public const ACTION_OWN_DEBIT_CARD = 25;

    /**
     * Usage type constants.
     */
    public const USE_TYPE_BOTH = 0;

    public const USE_TYPE_REGISTRATION = 1;

    public const USE_TYPE_PAYMENT_METHOD = 2;

    /**
     * Usage location constants.
     */
    public const USE_LOCATION_BOTH = 0;

    public const USE_LOCATION_RECEIVABLE = 1;

    public const USE_LOCATION_PAYABLE = 2;

    /**
     * Filter by action type.
     *
     * @param  int  $action  Use ACTION_* constants
     */
    public function byAction(int $action): Builder
    {
        return $this->query()->where('acao', $action);
    }

    /**
     * Filter by usage type.
     *
     * @param  int  $type  Use USE_TYPE_* constants
     */
    public function byUsageType(int $type): Builder
    {
        return $this->query()->where('tipoUso', $type);
    }

    /**
     * Filter by usage location.
     *
     * @param  int  $location  Use USE_LOCATION_* constants
     */
    public function byUsageLocation(int $location): Builder
    {
        return $this->query()->where('localUso', $location);
    }

    /**
     * Get only active document types.
     */
    public function active(): Builder
    {
        return $this->query()->where('inativo', 0);
    }

    /**
     * Get only inactive document types.
     */
    public function inactive(): Builder
    {
        return $this->query()->where('inativo', 1);
    }

    /**
     * Get document types that generate commission on discharge.
     */
    public function generatesCommission(): Builder
    {
        return $this->query()->where('baixaGeraComissao', 1);
    }

    /**
     * Get document types that allow boleto generation.
     */
    public function allowsBoleto(): Builder
    {
        return $this->query()->where('permiteGerarBoleto', 1);
    }

    /**
     * Get document types used in negotiation.
     */
    public function usedInNegotiation(): Builder
    {
        return $this->query()->where('utilizadoEmNegociacao', 1);
    }

    /**
     * Get document types for mobile.
     */
    public function mobile(): Builder
    {
        return $this->query()->where('enviaMobile', 1);
    }

    /**
     * Get document types for e-commerce.
     */
    public function ecommerce(): Builder
    {
        return $this->query()->where('enviaEcommerce', 1);
    }

    /**
     * Get document types for receivables (contas a receber).
     */
    public function receivables(): Builder
    {
        return $this->byUsageLocation(self::USE_LOCATION_RECEIVABLE);
    }

    /**
     * Get document types for payables (contas a pagar).
     */
    public function payables(): Builder
    {
        return $this->byUsageLocation(self::USE_LOCATION_PAYABLE);
    }

    /**
     * Get credit card document types.
     */
    public function creditCard(): Builder
    {
        return $this->byAction(self::ACTION_CREDIT_CARD);
    }

    /**
     * Get debit card document types.
     */
    public function debitCard(): Builder
    {
        return $this->byAction(self::ACTION_DEBIT_CARD);
    }

    /**
     * Get PIX document types.
     */
    public function pix(): Builder
    {
        return $this->byAction(self::ACTION_PIX);
    }

    /**
     * Get digital wallet document types.
     */
    public function digitalWallet(): Builder
    {
        return $this->byAction(self::ACTION_DIGITAL_WALLET);
    }
}
