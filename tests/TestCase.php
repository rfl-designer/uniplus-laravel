<?php

declare(strict_types=1);

namespace Uniplus\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Uniplus\UniplusServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            UniplusServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Uniplus' => \Uniplus\Facades\Uniplus::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('uniplus.default', 'testing');
        $app['config']->set('uniplus.connections.testing', [
            'account' => 'test-account',
            'authorization_code' => base64_encode('test:token'),
            'user_id' => 1,
            'branch_id' => 1,
        ]);
        $app['config']->set('uniplus.cache.enabled', false);
        $app['config']->set('uniplus.logging.enabled', false);
    }
}
