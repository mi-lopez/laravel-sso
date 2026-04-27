<?php

use Zefy\LaravelSSO\SSOServiceProvider;

it('is registered in the application', function () {
    expect($this->app->getProviders(SSOServiceProvider::class))->not->toBeEmpty();
});

it('publishes the config file', function () {
    $this->artisan('vendor:publish', [
        '--provider' => SSOServiceProvider::class,
        '--force' => true,
    ])->assertExitCode(0);
});
