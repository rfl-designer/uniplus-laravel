<?php

declare(strict_types=1);

use Uniplus\Testing\FakeClient;

describe('FakeClient', function () {
    it('can be created with predefined responses', function () {
        $fakeClient = new FakeClient([
            'produtos' => ['id' => 1, 'name' => 'Product 1'],
            'entidades' => ['id' => 2, 'name' => 'Entity 1'],
        ]);

        expect($fakeClient)->toBeInstanceOf(FakeClient::class);
    });

    it('returns exact match response', function () {
        $fakeClient = new FakeClient([
            'produtos' => ['id' => 1, 'name' => 'Product 1'],
        ]);

        $response = $fakeClient->getResponse('produtos');

        expect($response)->toBe(['id' => 1, 'name' => 'Product 1']);
    });

    it('returns partial match response', function () {
        $fakeClient = new FakeClient([
            'produtos' => ['id' => 1, 'name' => 'Product 1'],
        ]);

        $response = $fakeClient->getResponse('public-api/v1/produtos');

        expect($response)->toBe(['id' => 1, 'name' => 'Product 1']);
    });

    it('returns null for no match', function () {
        $fakeClient = new FakeClient([
            'produtos' => ['id' => 1],
        ]);

        $response = $fakeClient->getResponse('unknown-endpoint');

        expect($response)->toBeNull();
    });

    it('records requests', function () {
        $fakeClient = new FakeClient;

        $fakeClient->record('GET', '/produtos', []);
        $fakeClient->record('POST', '/entidades', ['name' => 'Test']);

        $recorded = $fakeClient->recorded();

        expect($recorded)->toHaveCount(2)
            ->and($recorded[0]['method'])->toBe('GET')
            ->and($recorded[0]['url'])->toBe('/produtos')
            ->and($recorded[1]['method'])->toBe('POST')
            ->and($recorded[1]['payload'])->toBe(['name' => 'Test']);
    });

    it('asserts request was sent', function () {
        $fakeClient = new FakeClient;

        $fakeClient->record('GET', '/produtos', []);

        // This should not throw
        $fakeClient->assertSent('GET', '/produtos');
    });

    it('fails assertion when request was not sent', function () {
        $fakeClient = new FakeClient;

        expect(fn () => $fakeClient->assertSent('GET', '/produtos'))
            ->toThrow(\PHPUnit\Framework\AssertionFailedError::class);
    });

    it('asserts request was not sent', function () {
        $fakeClient = new FakeClient;

        $fakeClient->record('GET', '/produtos', []);

        // This should not throw
        $fakeClient->assertNotSent('/entidades');
    });

    it('fails assertion when request was unexpectedly sent', function () {
        $fakeClient = new FakeClient;

        $fakeClient->record('GET', '/produtos', []);

        expect(fn () => $fakeClient->assertNotSent('/produtos'))
            ->toThrow(\PHPUnit\Framework\AssertionFailedError::class);
    });

    it('asserts correct request count', function () {
        $fakeClient = new FakeClient;

        $fakeClient->record('GET', '/produtos', []);
        $fakeClient->record('POST', '/entidades', []);
        $fakeClient->record('PUT', '/davs', []);

        // This should not throw
        $fakeClient->assertSentCount(3);
    });

    it('fails assertion for incorrect request count', function () {
        $fakeClient = new FakeClient;

        $fakeClient->record('GET', '/produtos', []);

        expect(fn () => $fakeClient->assertSentCount(5))
            ->toThrow(\PHPUnit\Framework\AssertionFailedError::class);
    });

    it('asserts nothing was sent', function () {
        $fakeClient = new FakeClient;

        // This should not throw
        $fakeClient->assertNothingSent();
    });

    it('fails assertion when something was sent but nothing expected', function () {
        $fakeClient = new FakeClient;

        $fakeClient->record('GET', '/produtos', []);

        expect(fn () => $fakeClient->assertNothingSent())
            ->toThrow(\PHPUnit\Framework\AssertionFailedError::class);
    });

    it('returns recorded requests as collection', function () {
        $fakeClient = new FakeClient;

        $fakeClient->record('GET', '/produtos', []);
        $fakeClient->record('POST', '/entidades', []);

        $collection = $fakeClient->getRecorded();

        expect($collection)->toHaveCount(2)
            ->and($collection->first()['method'])->toBe('GET');
    });

    it('can clear recorded requests', function () {
        $fakeClient = new FakeClient;

        $fakeClient->record('GET', '/produtos', []);
        $fakeClient->record('POST', '/entidades', []);

        $fakeClient->clear();

        expect($fakeClient->recorded())->toHaveCount(0);
    });

    it('can add response after construction', function () {
        $fakeClient = new FakeClient;

        $fakeClient->addResponse('produtos', ['id' => 1, 'name' => 'New Product']);

        $response = $fakeClient->getResponse('produtos');

        expect($response)->toBe(['id' => 1, 'name' => 'New Product']);
    });

    it('can update existing response', function () {
        $fakeClient = new FakeClient([
            'produtos' => ['id' => 1, 'name' => 'Old Product'],
        ]);

        $fakeClient->addResponse('produtos', ['id' => 2, 'name' => 'Updated Product']);

        $response = $fakeClient->getResponse('produtos');

        expect($response)->toBe(['id' => 2, 'name' => 'Updated Product']);
    });

    it('returns fluent interface from addResponse', function () {
        $fakeClient = new FakeClient;

        $result = $fakeClient
            ->addResponse('produtos', ['id' => 1])
            ->addResponse('entidades', ['id' => 2]);

        expect($result)->toBeInstanceOf(FakeClient::class);
    });
});
