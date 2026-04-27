<?php

namespace Zefy\LaravelSSO;

use Illuminate\Support\ServiceProvider;

class SSOServiceProvider extends ServiceProvider
{
    protected string $configFileName = 'laravel-sso.php';

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/'.$this->configFileName => config_path($this->configFileName),
        ]);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\CreateBroker::class,
                Commands\DeleteBroker::class,
                Commands\ListBrokers::class,
            ]);
        }

        if (config('laravel-sso.type') === 'server') {
            $this->loadRoutesFrom(__DIR__.'/Routes/server.php');
        }
    }
}
