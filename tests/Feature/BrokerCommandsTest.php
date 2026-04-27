<?php

use Zefy\LaravelSSO\Models\Broker;

it('creates a broker with a generated secret', function () {
    $this->artisan('sso:broker:create', ['name' => 'demo-broker'])
        ->assertExitCode(0);

    $broker = Broker::where('name', 'demo-broker')->first();

    expect($broker)->not->toBeNull()
        ->and($broker->secret)->toBeString()
        ->and(strlen($broker->secret))->toBe(40);
});

it('lists all brokers', function () {
    Broker::create(['name' => 'broker-a', 'secret' => 'secret-a']);
    Broker::create(['name' => 'broker-b', 'secret' => 'secret-b']);

    $this->artisan('sso:broker:list')
        ->expectsOutputToContain('broker-a')
        ->expectsOutputToContain('broker-b')
        ->assertExitCode(0);
});

it('deletes a broker by name', function () {
    Broker::create(['name' => 'doomed', 'secret' => 'secret']);

    $this->artisan('sso:broker:delete', ['name' => 'doomed'])
        ->assertExitCode(0);

    expect(Broker::where('name', 'doomed')->exists())->toBeFalse();
});

it('fails to delete a broker that does not exist', function () {
    $this->artisan('sso:broker:delete', ['name' => 'ghost'])
        ->assertFailed();
});
