<?php

declare(strict_types=1);

use Illuminate\Http\Client\Response as HttpResponse;
use Uniplus\Http\Response;

beforeEach(function () {
    $this->httpResponse = Mockery::mock(HttpResponse::class);
});

afterEach(function () {
    Mockery::close();
});

describe('Response', function () {
    it('returns status code', function () {
        $this->httpResponse->shouldReceive('status')->andReturn(200);

        $response = new Response($this->httpResponse);

        expect($response->status())->toBe(200);
    });

    it('checks if request was successful', function () {
        $this->httpResponse->shouldReceive('successful')->andReturn(true);

        $response = new Response($this->httpResponse);

        expect($response->successful())->toBeTrue();
    });

    it('checks if request failed', function () {
        $this->httpResponse->shouldReceive('failed')->andReturn(true);

        $response = new Response($this->httpResponse);

        expect($response->failed())->toBeTrue();
    });

    it('returns body as string', function () {
        $this->httpResponse->shouldReceive('body')->andReturn('{"data": "test"}');

        $response = new Response($this->httpResponse);

        expect($response->body())->toBe('{"data": "test"}');
    });

    it('returns json without key', function () {
        $this->httpResponse->shouldReceive('json')
            ->with(null)
            ->andReturn(['data' => 'test', 'status' => 'ok']);

        $response = new Response($this->httpResponse);

        expect($response->json())->toBe(['data' => 'test', 'status' => 'ok']);
    });

    it('returns json with specific key', function () {
        $this->httpResponse->shouldReceive('json')
            ->with('data')
            ->andReturn('test');

        $response = new Response($this->httpResponse);

        expect($response->json('data'))->toBe('test');
    });

    it('returns data as collection', function () {
        $this->httpResponse->shouldReceive('json')
            ->andReturn([
                ['id' => 1, 'name' => 'Item 1'],
                ['id' => 2, 'name' => 'Item 2'],
            ]);

        $response = new Response($this->httpResponse);
        $collection = $response->collect();

        expect($collection)->toHaveCount(2)
            ->and($collection->first())->toBe(['id' => 1, 'name' => 'Item 1']);
    });

    it('returns empty collection when json is null', function () {
        $this->httpResponse->shouldReceive('json')->andReturn(null);

        $response = new Response($this->httpResponse);
        $collection = $response->collect();

        expect($collection)->toHaveCount(0);
    });

    it('returns a specific header', function () {
        $this->httpResponse->shouldReceive('header')
            ->with('Content-Type')
            ->andReturn('application/json');

        $response = new Response($this->httpResponse);

        expect($response->header('Content-Type'))->toBe('application/json');
    });

    it('returns null for missing header', function () {
        $this->httpResponse->shouldReceive('header')
            ->with('X-Missing')
            ->andReturn(null);

        $response = new Response($this->httpResponse);

        expect($response->header('X-Missing'))->toBeNull();
    });

    it('returns all headers', function () {
        $headers = [
            'Content-Type' => ['application/json'],
            'X-Request-Id' => ['abc123'],
        ];
        $this->httpResponse->shouldReceive('headers')->andReturn($headers);

        $response = new Response($this->httpResponse);

        expect($response->headers())->toBe($headers);
    });

    it('checks if response is client error', function () {
        $this->httpResponse->shouldReceive('clientError')->andReturn(true);

        $response = new Response($this->httpResponse);

        expect($response->clientError())->toBeTrue();
    });

    it('checks if response is server error', function () {
        $this->httpResponse->shouldReceive('serverError')->andReturn(true);

        $response = new Response($this->httpResponse);

        expect($response->serverError())->toBeTrue();
    });

    it('returns the underlying http response', function () {
        $response = new Response($this->httpResponse);

        expect($response->toHttpResponse())->toBe($this->httpResponse);
    });
});
