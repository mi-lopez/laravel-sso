<?php

use Zefy\LaravelSSO\Exceptions\MissingConfigurationException;
use Zefy\LaravelSSO\LaravelSSOBroker;

beforeEach(function () {
    config()->set('laravel-sso.serverUrl', 'https://sso.test');
    config()->set('laravel-sso.brokerName', 'my-broker');
    config()->set('laravel-sso.brokerSecret', 'super-secret');

    // Pre-seed the cookie so the broker constructor doesn't trigger an attach redirect.
    request()->cookies->set('sso_token_my_broker', 'fixed-token');
});

it('throws when configuration is missing', function () {
    config()->set('laravel-sso.serverUrl', null);

    new LaravelSSOBroker;
})->throws(MissingConfigurationException::class);

it('reuses the token from the cookie when present', function () {
    $broker = new LaravelSSOBroker;

    $token = (new ReflectionClass($broker))
        ->getProperty('token')
        ->getValue($broker);

    expect($token)->toBe('fixed-token');
});

it('builds the cookie name from the broker name', function () {
    $broker = new LaravelSSOBroker;

    $name = (new ReflectionClass($broker))
        ->getMethod('getCookieName')
        ->invoke($broker);

    expect($name)->toBe('sso_token_my_broker');
});

it('normalizes broker names with mixed case and special chars in the cookie', function () {
    config()->set('laravel-sso.brokerName', 'My Broker.1');
    request()->cookies->set('sso_token_my_broker_1', 'fixed-token');

    $broker = new LaravelSSOBroker;

    $name = (new ReflectionClass($broker))
        ->getMethod('getCookieName')
        ->invoke($broker);

    expect($name)->toBe('sso_token_my_broker_1');
});

it('builds command URLs against the configured SSO server', function () {
    $broker = new LaravelSSOBroker;

    $url = (new ReflectionClass($broker))
        ->getMethod('generateCommandUrl')
        ->invoke($broker, 'login', ['foo' => 'bar']);

    expect($url)->toBe('https://sso.test/api/sso/login?foo=bar');
});

it('builds command URLs without query when no parameters', function () {
    $broker = new LaravelSSOBroker;

    $url = (new ReflectionClass($broker))
        ->getMethod('generateCommandUrl')
        ->invoke($broker, 'userInfo');

    expect($url)->toBe('https://sso.test/api/sso/userInfo');
});

it('produces a deterministic broker session id from token and secret', function () {
    $broker = new LaravelSSOBroker;

    $sessionId = (new ReflectionClass($broker))
        ->getMethod('getSessionId')
        ->invoke($broker);

    $expectedChecksum = hash('sha256', 'session'.'fixed-token'.'super-secret');
    expect($sessionId)->toBe('SSO-my-broker-fixed-token-'.$expectedChecksum);
});
