<?php

use Illuminate\Support\Facades\Cache;
use Zefy\LaravelSSO\LaravelSSOServer;
use Zefy\LaravelSSO\Models\Broker;

it('caches and retrieves broker session data', function () {
    $server = new LaravelSSOServer;
    $reflection = new ReflectionClass($server);

    $reflection->getMethod('saveBrokerSessionData')->invoke($server, 'broker-id', 'server-session');

    expect(Cache::get('broker_session:broker-id'))->toBe('server-session');

    $retrieved = $reflection->getMethod('getBrokerSessionData')->invoke($server, 'broker-id');
    expect($retrieved)->toBe('server-session');
});

it('returns null when broker session data is missing', function () {
    $server = new LaravelSSOServer;

    $retrieved = (new ReflectionClass($server))
        ->getMethod('getBrokerSessionData')
        ->invoke($server, 'unknown');

    expect($retrieved)->toBeNull();
});

it('extracts broker session id from Bearer authorization header', function () {
    request()->headers->set('Authorization', 'Bearer SSO-broker1-abc-checksum');

    $server = new LaravelSSOServer;
    $sessionId = (new ReflectionClass($server))
        ->getMethod('getBrokerSessionId')
        ->invoke($server);

    expect($sessionId)->toBe('SSO-broker1-abc-checksum');
});

it('returns null when authorization header is missing', function () {
    $server = new LaravelSSOServer;
    $sessionId = (new ReflectionClass($server))
        ->getMethod('getBrokerSessionId')
        ->invoke($server);

    expect($sessionId)->toBeNull();
});

it('returns null when authorization header is not a Bearer token', function () {
    request()->headers->set('Authorization', 'Basic dXNlcjpwYXNz');

    $server = new LaravelSSOServer;
    $sessionId = (new ReflectionClass($server))
        ->getMethod('getBrokerSessionId')
        ->invoke($server);

    expect($sessionId)->toBeNull();
});

it('looks up broker by name', function () {
    Broker::create(['name' => 'broker1', 'secret' => 'secret']);

    $server = new LaravelSSOServer;
    $broker = (new ReflectionClass($server))
        ->getMethod('getBrokerInfo')
        ->invoke($server, 'broker1');

    expect($broker)->not->toBeNull()
        ->and($broker->name)->toBe('broker1')
        ->and($broker->secret)->toBe('secret');
});

it('returns null when broker does not exist', function () {
    $server = new LaravelSSOServer;
    $broker = (new ReflectionClass($server))
        ->getMethod('getBrokerInfo')
        ->invoke($server, 'unknown');

    expect($broker)->toBeNull();
});
