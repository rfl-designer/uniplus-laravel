<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Uniplus\Http\Client;
use Uniplus\Http\Response;
use Uniplus\Query\Builder;

beforeEach(function () {
    $this->client = Mockery::mock(Client::class);
});

afterEach(function () {
    Mockery::close();
});

describe('Builder', function () {
    it('can add where clause with two arguments', function () {
        $builder = new Builder($this->client, 'test-endpoint');

        $result = $builder->where('field', 'value');

        expect($result)->toBeInstanceOf(Builder::class)
            ->and($result->toQueryString())->toBe('field.eq=value');
    });

    it('can add where clause with three arguments', function () {
        $builder = new Builder($this->client, 'test-endpoint');

        $result = $builder->where('field', '>=', 100);

        expect($result->toQueryString())->toBe('field.ge=100');
    });

    it('can chain multiple where clauses', function () {
        $builder = new Builder($this->client, 'test-endpoint');

        $result = $builder
            ->where('status', 'active')
            ->where('quantity', '>', 0);

        expect($result->toQueryString())->toContain('status.eq=active')
            ->and($result->toQueryString())->toContain('quantity.gt=0');
    });

    it('can use whereEquals shorthand', function () {
        $builder = new Builder($this->client, 'test-endpoint');

        $result = $builder->whereEquals('status', 'active');

        expect($result->toQueryString())->toBe('status.eq=active');
    });

    it('can use whereNotEquals shorthand', function () {
        $builder = new Builder($this->client, 'test-endpoint');

        $result = $builder->whereNotEquals('status', 'inactive');

        expect($result->toQueryString())->toBe('status.ne=inactive');
    });

    it('can use whereGreaterThan shorthand', function () {
        $builder = new Builder($this->client, 'test-endpoint');

        $result = $builder->whereGreaterThan('price', 100);

        expect($result->toQueryString())->toBe('price.gt=100');
    });

    it('can use whereGreaterThanOrEqual shorthand', function () {
        $builder = new Builder($this->client, 'test-endpoint');

        $result = $builder->whereGreaterThanOrEqual('price', 100);

        expect($result->toQueryString())->toBe('price.ge=100');
    });

    it('can use whereLessThan shorthand', function () {
        $builder = new Builder($this->client, 'test-endpoint');

        $result = $builder->whereLessThan('price', 50);

        expect($result->toQueryString())->toBe('price.lt=50');
    });

    it('can use whereLessThanOrEqual shorthand', function () {
        $builder = new Builder($this->client, 'test-endpoint');

        $result = $builder->whereLessThanOrEqual('price', 50);

        expect($result->toQueryString())->toBe('price.le=50');
    });

    it('can set limit', function () {
        $builder = new Builder($this->client, 'test-endpoint');

        $result = $builder->limit(10);

        expect($result->toQueryString())->toBe('limit=10');
    });

    it('can use take as alias for limit', function () {
        $builder = new Builder($this->client, 'test-endpoint');

        $result = $builder->take(20);

        expect($result->toQueryString())->toBe('limit=20');
    });

    it('can set offset', function () {
        $builder = new Builder($this->client, 'test-endpoint');

        $result = $builder->offset(50);

        expect($result->toQueryString())->toBe('offset=50');
    });

    it('can use skip as alias for offset', function () {
        $builder = new Builder($this->client, 'test-endpoint');

        $result = $builder->skip(100);

        expect($result->toQueryString())->toBe('offset=100');
    });

    it('can combine where, limit, and offset', function () {
        $builder = new Builder($this->client, 'test-endpoint');

        $result = $builder
            ->where('status', 'active')
            ->limit(10)
            ->offset(20);

        $query = $result->toQueryString();

        expect($query)->toContain('status.eq=active')
            ->and($query)->toContain('limit=10')
            ->and($query)->toContain('offset=20');
    });

    it('executes get and returns collection', function () {
        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')
            ->andReturn([
                ['id' => 1, 'name' => 'Product 1'],
                ['id' => 2, 'name' => 'Product 2'],
            ]);

        $this->client->shouldReceive('get')
            ->once()
            ->andReturn($mockResponse);

        $builder = new Builder($this->client, 'test-endpoint');
        $result = $builder->get();

        expect($result)->toHaveCount(2)
            ->and($result->first())->toBe(['id' => 1, 'name' => 'Product 1']);
    });

    it('executes first and returns single item', function () {
        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')
            ->andReturn([['id' => 1, 'name' => 'First Product']]);

        $this->client->shouldReceive('get')
            ->once()
            ->andReturn($mockResponse);

        $builder = new Builder($this->client, 'test-endpoint');
        $result = $builder->first();

        expect($result)->toBe(['id' => 1, 'name' => 'First Product']);
    });

    it('returns null when first finds nothing', function () {
        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')->andReturn([]);

        $this->client->shouldReceive('get')
            ->once()
            ->andReturn($mockResponse);

        $builder = new Builder($this->client, 'test-endpoint');
        $result = $builder->first();

        expect($result)->toBeNull();
    });

    it('can count results', function () {
        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')
            ->andReturn([['id' => 1], ['id' => 2], ['id' => 3]]);

        $this->client->shouldReceive('get')
            ->once()
            ->andReturn($mockResponse);

        $builder = new Builder($this->client, 'test-endpoint');
        $result = $builder->count();

        expect($result)->toBe(3);
    });

    it('can check if results exist', function () {
        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')
            ->andReturn([['id' => 1]]);

        $this->client->shouldReceive('get')
            ->once()
            ->andReturn($mockResponse);

        $builder = new Builder($this->client, 'test-endpoint');
        $result = $builder->exists();

        expect($result)->toBeTrue();
    });

    it('returns false for exists when no results', function () {
        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('json')->andReturn([]);

        $this->client->shouldReceive('get')
            ->once()
            ->andReturn($mockResponse);

        $builder = new Builder($this->client, 'test-endpoint');
        $result = $builder->exists();

        expect($result)->toBeFalse();
    });

    it('handles DateTime values in where clauses', function () {
        $date = Carbon::create(2024, 6, 15);
        $builder = new Builder($this->client, 'test-endpoint');

        $result = $builder->where('created_at', '>=', $date);

        expect($result->toQueryString())->toBe('created_at.ge=2024-06-15');
    });

    it('normalizes non-scalar values to null', function () {
        $builder = new Builder($this->client, 'test-endpoint');

        // Arrays and objects should be normalized to null
        $result = $builder->where('field', ['array']);

        expect($result->toQueryString())->toBe('field.eq=');
    });
});
