<?php

declare(strict_types=1);

namespace Uniplus\Resources;

use Uniplus\Query\Builder;

/**
 * Resource for Gourmet accounts (Conta Gourmet).
 *
 * Endpoint: /public-api/v1/gourmet/conta
 * Supported methods: GET, POST
 *
 * The Gourmet API is designed for integration with order platforms
 * in food service establishments. Allows creating and querying
 * consumption accounts (tables and tabs).
 */
class ContaGourmet extends Resource
{
    protected string $endpoint = 'public-api/v1/gourmet/conta';

    protected string $primaryKey = 'numero';

    /**
     * Account type constants.
     */
    public const TYPE_TABLE = 'MESA';

    public const TYPE_TAB = 'COMANDA';

    /**
     * Optional type constants.
     */
    public const OPTIONAL_WITH = 'COM';

    public const OPTIONAL_WITHOUT = 'SEM';

    /**
     * Find an account by number, type and branch.
     *
     * @param  int  $number  Account number
     * @param  string  $type  Account type (MESA or COMANDA)
     * @param  string  $branchCode  Branch code
     * @param  bool  $includeItems  Whether to include items in the response
     * @return array<string, mixed>
     */
    public function findAccount(int $number, string $type, string $branchCode, bool $includeItems = true): array
    {
        $response = $this->client->get($this->endpoint, [
            'numero' => $number,
            'tipo' => $type,
            'codigoFilial' => $branchCode,
            'incluirItens' => $includeItems ? 'true' : 'false',
        ]);

        /** @var array<string, mixed> $data */
        $data = $response->json() ?? [];

        return $data;
    }

    /**
     * Find a table account.
     *
     * @param  int  $tableNumber  Table number
     * @param  string  $branchCode  Branch code
     * @param  bool  $includeItems  Whether to include items
     * @return array<string, mixed>
     */
    public function findTable(int $tableNumber, string $branchCode, bool $includeItems = true): array
    {
        return $this->findAccount($tableNumber, self::TYPE_TABLE, $branchCode, $includeItems);
    }

    /**
     * Find a tab account.
     *
     * @param  int  $tabNumber  Tab number
     * @param  string  $branchCode  Branch code
     * @param  bool  $includeItems  Whether to include items
     * @return array<string, mixed>
     */
    public function findTab(int $tabNumber, string $branchCode, bool $includeItems = true): array
    {
        return $this->findAccount($tabNumber, self::TYPE_TAB, $branchCode, $includeItems);
    }

    /**
     * Create or update an account.
     *
     * This method sends the order to production. Note that POST does not
     * allow editing existing items, only adding new items.
     *
     * @param  array<string, mixed>  $data  Account data
     * @return array<string, mixed>
     */
    public function createOrUpdate(array $data): array
    {
        return $this->create($data);
    }

    /**
     * Create a table account.
     *
     * @param  int  $tableNumber  Table number
     * @param  string  $branchCode  Branch code
     * @param  array<int, array<string, mixed>>  $items  List of items
     * @param  array<string, mixed>  $additionalData  Additional data (observacao, valorDescontoSubtotal, cliente, pagamentos)
     * @return array<string, mixed>
     */
    public function createTable(int $tableNumber, string $branchCode, array $items, array $additionalData = []): array
    {
        $data = array_merge([
            'numero' => $tableNumber,
            'tipo' => self::TYPE_TABLE,
            'codigoFilial' => $branchCode,
            'itens' => $items,
            'valorTotalItens' => $this->calculateItemsTotal($items),
        ], $additionalData);

        return $this->create($data);
    }

    /**
     * Create a tab account.
     *
     * @param  int  $tabNumber  Tab number
     * @param  string  $branchCode  Branch code
     * @param  array<int, array<string, mixed>>  $items  List of items
     * @param  array<string, mixed>  $additionalData  Additional data (observacao, valorDescontoSubtotal, cliente, pagamentos)
     * @return array<string, mixed>
     */
    public function createTab(int $tabNumber, string $branchCode, array $items, array $additionalData = []): array
    {
        $data = array_merge([
            'numero' => $tabNumber,
            'tipo' => self::TYPE_TAB,
            'codigoFilial' => $branchCode,
            'itens' => $items,
            'valorTotalItens' => $this->calculateItemsTotal($items),
        ], $additionalData);

        return $this->create($data);
    }

    /**
     * Add items to an existing account.
     *
     * @param  int  $number  Account number
     * @param  string  $type  Account type (MESA or COMANDA)
     * @param  string  $branchCode  Branch code
     * @param  array<int, array<string, mixed>>  $items  New items to add
     * @return array<string, mixed>
     */
    public function addItems(int $number, string $type, string $branchCode, array $items): array
    {
        $data = [
            'numero' => $number,
            'tipo' => $type,
            'codigoFilial' => $branchCode,
            'itens' => $items,
            'valorTotalItens' => $this->calculateItemsTotal($items),
        ];

        return $this->create($data);
    }

    /**
     * Build an item structure for the account.
     *
     * @param  string  $productCode  Product code
     * @param  string  $productName  Product name
     * @param  float  $quantity  Quantity
     * @param  float  $unitPrice  Unit price
     * @param  string  $unitMeasure  Unit of measure
     * @param  array<string, mixed>  $additional  Additional data (observacao, valorDescontoSubtotal, opcionais)
     * @return array<string, mixed>
     */
    public function buildItem(
        string $productCode,
        string $productName,
        float $quantity,
        float $unitPrice,
        string $unitMeasure = 'UN',
        array $additional = [],
    ): array {
        /** @var float|int|string $discount */
        $discount = $additional['valorDescontoSubtotal'] ?? 0;
        $valorTotal = ($quantity * $unitPrice) - (float) $discount;

        return array_merge([
            'codigoProduto' => $productCode,
            'nomeProduto' => $productName,
            'quantidade' => number_format($quantity, 3, '.', ''),
            'valorUnitario' => number_format($unitPrice, 3, '.', ''),
            'valorTotal' => number_format($valorTotal, 3, '.', ''),
            'codigoUnidadeMedida' => $unitMeasure,
        ], $additional);
    }

    /**
     * Build an optional item (addon or removal).
     *
     * @param  string  $type  Optional type (COM or SEM)
     * @param  string  $productCode  Product code
     * @param  string  $productName  Product name
     * @param  float  $quantity  Quantity
     * @param  float  $unitPrice  Unit price (0 for removals)
     * @return array<string, mixed>
     */
    public function buildOptional(
        string $type,
        string $productCode,
        string $productName,
        float $quantity = 1.0,
        float $unitPrice = 0.0,
    ): array {
        return [
            'tipo' => $type,
            'codigoProduto' => $productCode,
            'nomeProduto' => $productName,
            'quantidade' => number_format($quantity, 3, '.', ''),
            'valorUnitario' => number_format($unitPrice, 3, '.', ''),
            'valorTotal' => number_format($quantity * $unitPrice, 2, '.', ''),
        ];
    }

    /**
     * Build an addon optional (COM).
     *
     * @param  string  $productCode  Product code
     * @param  string  $productName  Product name
     * @param  float  $quantity  Quantity
     * @param  float  $unitPrice  Unit price
     * @return array<string, mixed>
     */
    public function buildAddon(string $productCode, string $productName, float $quantity = 1.0, float $unitPrice = 0.0): array
    {
        return $this->buildOptional(self::OPTIONAL_WITH, $productCode, $productName, $quantity, $unitPrice);
    }

    /**
     * Build a removal optional (SEM).
     *
     * @param  string  $productCode  Product code
     * @param  string  $productName  Product name
     * @return array<string, mixed>
     */
    public function buildRemoval(string $productCode, string $productName): array
    {
        return $this->buildOptional(self::OPTIONAL_WITHOUT, $productCode, $productName, 1.0, 0.0);
    }

    /**
     * Calculate the total value of items.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function calculateItemsTotal(array $items): string
    {
        $total = 0.0;

        foreach ($items as $item) {
            /** @var float|int|string $itemTotal */
            $itemTotal = $item['valorTotal'] ?? 0;
            $total += (float) $itemTotal;
        }

        return number_format($total, 2, '.', '');
    }

    /**
     * Filter accounts by branch.
     */
    public function byBranch(string $branchCode): Builder
    {
        return $this->query()->where('codigoFilial', $branchCode);
    }

    /**
     * Filter accounts by type.
     *
     * @param  string  $type  Account type (MESA or COMANDA)
     */
    public function byType(string $type): Builder
    {
        return $this->query()->where('tipo', $type);
    }

    /**
     * Get all table accounts.
     */
    public function tables(): Builder
    {
        return $this->byType(self::TYPE_TABLE);
    }

    /**
     * Get all tab accounts.
     */
    public function tabs(): Builder
    {
        return $this->byType(self::TYPE_TAB);
    }

    /**
     * Update is not typically supported for Gourmet accounts.
     * Use createOrUpdate (POST) to add new items.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(array $data): array
    {
        return $this->create($data);
    }

    /**
     * Delete is not supported for Gourmet accounts.
     */
    public function delete(string $code): bool
    {
        return false;
    }
}
