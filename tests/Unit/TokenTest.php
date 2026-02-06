<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Uniplus\Auth\Token;

beforeEach(function () {
    Carbon::setTestNow(Carbon::create(2024, 1, 1, 12, 0, 0));
});

afterEach(function () {
    Carbon::setTestNow();
});

describe('Token', function () {
    it('can be created from response data', function () {
        $data = [
            'access_token' => 'test-access-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => 'public-api',
        ];

        $token = Token::fromResponse($data);

        expect($token->accessToken)->toBe('test-access-token')
            ->and($token->tokenType)->toBe('Bearer')
            ->and($token->expiresIn)->toBe(3600)
            ->and($token->scope)->toBe('public-api');
    });

    it('uses default values when response data is incomplete', function () {
        $data = [
            'access_token' => 'test-token',
        ];

        $token = Token::fromResponse($data);

        expect($token->accessToken)->toBe('test-token')
            ->and($token->tokenType)->toBe('Bearer')
            ->and($token->expiresIn)->toBe(3600)
            ->and($token->scope)->toBe('public-api');
    });

    it('calculates expiration time correctly', function () {
        $data = [
            'access_token' => 'test-token',
            'expires_in' => 3600,
        ];

        $token = Token::fromResponse($data);

        expect($token->expiresAt->format('Y-m-d H:i:s'))
            ->toBe('2024-01-01 13:00:00');
    });

    it('detects when token is not expired', function () {
        $data = [
            'access_token' => 'test-token',
            'expires_in' => 3600,
        ];

        $token = Token::fromResponse($data);

        expect($token->isExpired())->toBeFalse();
    });

    it('detects when token is expired', function () {
        $data = [
            'access_token' => 'test-token',
            'expires_in' => 3600,
        ];

        $token = Token::fromResponse($data);

        Carbon::setTestNow(Carbon::create(2024, 1, 1, 14, 0, 0));

        expect($token->isExpired())->toBeTrue();
    });

    it('detects when token expires within given seconds', function () {
        $data = [
            'access_token' => 'test-token',
            'expires_in' => 3600,
        ];

        $token = Token::fromResponse($data);

        // Token expires in 1 hour, check if it expires within 2 hours
        expect($token->expiresWithin(7200))->toBeTrue()
            ->and($token->expiresWithin(1800))->toBeFalse();
    });

    it('generates correct authorization header', function () {
        $data = [
            'access_token' => 'my-secret-token',
            'token_type' => 'Bearer',
        ];

        $token = Token::fromResponse($data);

        expect($token->getAuthorizationHeader())
            ->toBe('Bearer my-secret-token');
    });

    it('always uses capitalized Bearer in authorization header even when API returns lowercase', function () {
        // The Uniplus API returns "bearer" (lowercase) but the server is case-sensitive
        // and requires "Bearer" (capitalized) in the Authorization header
        $data = [
            'access_token' => 'my-secret-token',
            'token_type' => 'bearer', // lowercase from API
        ];

        $token = Token::fromResponse($data);

        expect($token->getAuthorizationHeader())
            ->toBe('Bearer my-secret-token'); // Must be capitalized
    });

    it('can be serialized to array', function () {
        $data = [
            'access_token' => 'test-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => 'public-api',
        ];

        $token = Token::fromResponse($data);
        $array = $token->toArray();

        expect($array)->toHaveKeys(['access_token', 'token_type', 'expires_in', 'scope', 'expires_at'])
            ->and($array['access_token'])->toBe('test-token')
            ->and($array['token_type'])->toBe('Bearer')
            ->and($array['expires_in'])->toBe(3600)
            ->and($array['scope'])->toBe('public-api')
            ->and($array['expires_at'])->toBe('2024-01-01 13:00:00');
    });

    it('can be restored from cache', function () {
        $cachedData = [
            'access_token' => 'cached-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => 'public-api',
            'expires_at' => '2024-01-01 13:00:00',
        ];

        $token = Token::fromCache($cachedData);

        expect($token->accessToken)->toBe('cached-token')
            ->and($token->tokenType)->toBe('Bearer')
            ->and($token->expiresIn)->toBe(3600)
            ->and($token->scope)->toBe('public-api')
            ->and($token->expiresAt->format('Y-m-d H:i:s'))->toBe('2024-01-01 13:00:00');
    });

    it('handles expires_in as string from cache', function () {
        $cachedData = [
            'access_token' => 'cached-token',
            'token_type' => 'Bearer',
            'expires_in' => '3600',
            'scope' => 'public-api',
            'expires_at' => '2024-01-01 13:00:00',
        ];

        $token = Token::fromCache($cachedData);

        expect($token->expiresIn)->toBe(3600)
            ->and($token->expiresIn)->toBeInt();
    });
});
