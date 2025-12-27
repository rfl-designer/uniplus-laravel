<?php

declare(strict_types=1);

namespace Uniplus;

use Illuminate\Support\ServiceProvider;

class UniplusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/uniplus.php', 'uniplus');

        $this->app->singleton(UniplusManager::class, function ($app) {
            return new UniplusManager($app);
        });

        $this->app->bind('uniplus', function ($app) {
            return $app->make(UniplusManager::class);
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/uniplus.php' => config_path('uniplus.php'),
            ], 'uniplus-config');
        }
    }
}
