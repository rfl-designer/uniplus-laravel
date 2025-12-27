<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Uniplus\Query\Filter;

describe('Filter', function () {
    it('creates a filter with eq operator by default', function () {
        $filter = Filter::make('campo', '=', 'valor');

        expect($filter->field)->toBe('campo')
            ->and($filter->operator)->toBe('eq')
            ->and($filter->value)->toBe('valor');
    });

    it('maps common operators to API operators', function () {
        $operators = [
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

        foreach ($operators as $input => $expected) {
            $filter = Filter::make('field', $input, 'value');
            expect($filter->operator)->toBe($expected, "Operator '{$input}' should map to '{$expected}'");
        }
    });

    it('defaults to eq for unknown operators', function () {
        $filter = Filter::make('field', 'unknown', 'value');

        expect($filter->operator)->toBe('eq');
    });

    it('generates correct query string', function () {
        $filter = Filter::make('status', '=', 'active');

        expect($filter->toQueryString())->toBe('status.eq=active');
    });

    it('generates correct array representation', function () {
        $filter = Filter::make('status', '>=', 10);

        expect($filter->toArray())->toBe(['status.ge' => '10']);
    });

    it('formats boolean values correctly', function () {
        $filterTrue = Filter::make('active', '=', true);
        $filterFalse = Filter::make('inactive', '=', false);

        expect($filterTrue->toArray())->toBe(['active.eq' => '1'])
            ->and($filterFalse->toArray())->toBe(['inactive.eq' => '0']);
    });

    it('formats null values as empty string', function () {
        $filter = Filter::make('field', '=', null);

        expect($filter->toArray())->toBe(['field.eq' => '']);
    });

    it('formats DateTime values as Y-m-d', function () {
        $date = Carbon::create(2024, 3, 15);
        $filter = Filter::make('created_at', '>=', $date);

        expect($filter->toArray())->toBe(['created_at.ge' => '2024-03-15']);
    });

    it('formats integer values correctly', function () {
        $filter = Filter::make('quantity', '>', 100);

        expect($filter->toArray())->toBe(['quantity.gt' => '100']);
    });

    it('formats float values correctly', function () {
        $filter = Filter::make('price', '<=', 99.99);

        expect($filter->toArray())->toBe(['price.le' => '99.99']);
    });

    it('handles Stringable objects', function () {
        $stringable = new class implements Stringable
        {
            public function __toString(): string
            {
                return 'stringable-value';
            }
        };

        $filter = Filter::make('field', '=', $stringable);

        expect($filter->toArray())->toBe(['field.eq' => 'stringable-value']);
    });
});
