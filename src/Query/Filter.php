<?php

declare(strict_types=1);

namespace Uniplus\Query;

use DateTimeInterface;
use Stringable;

class Filter
{
    /**
     * Mapping of PHP operators to API operators.
     *
     * @var array<string, string>
     */
    protected const OPERATOR_MAP = [
        '=' => 'eq',
        '==' => 'eq',
        'eq' => 'eq',
        '!=' => 'ne',
        '<>' => 'ne',
        'ne' => 'ne',
        '>' => 'gt',
        'gt' => 'gt',
        '>=' => 'ge',
        'ge' => 'ge',
        '<' => 'lt',
        'lt' => 'lt',
        '<=' => 'le',
        'le' => 'le',
    ];

    public function __construct(
        public readonly string $field,
        public readonly string $operator,
        public readonly string|int|float|bool|DateTimeInterface|Stringable|null $value,
    ) {}

    /**
     * Create a filter from field, operator and value.
     */
    public static function make(string $field, string $operator, string|int|float|bool|DateTimeInterface|Stringable|null $value): self
    {
        $apiOperator = self::OPERATOR_MAP[$operator] ?? 'eq';

        return new self($field, $apiOperator, $value);
    }

    /**
     * Get the query string representation of this filter.
     */
    public function toQueryString(): string
    {
        $value = $this->formatValue($this->value);

        return "{$this->field}.{$this->operator}={$value}";
    }

    /**
     * Get the array representation for query building.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            "{$this->field}.{$this->operator}" => $this->formatValue($this->value),
        ];
    }

    /**
     * Format value for query string.
     */
    protected function formatValue(string|int|float|bool|DateTimeInterface|Stringable|null $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        return (string) $value;
    }
}
