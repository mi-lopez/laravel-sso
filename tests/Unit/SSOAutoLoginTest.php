<?php

use Illuminate\Http\Request;
use Zefy\LaravelSSO\Middleware\SSOAutoLogin;

beforeEach(function () {
    config()->set('laravel-sso.serverUrl', 'https://sso.test');
    config()->set('laravel-sso.brokerName', 'my-broker');
    config()->set('laravel-sso.brokerSecret', 'super-secret');
});

dataset('skipped paths', [
    'login',
    'logout',
    'register',
    'forgot-password',
    'reset-password',
    'login/anything',
    'register/extra',
]);

it('skips the SSO flow on auth paths', function (string $path) {
    $middleware = new SSOAutoLogin;
    $request = Request::create('/'.$path, 'GET');

    $shouldSkip = (new ReflectionClass($middleware))
        ->getMethod('shouldSkip')
        ->invoke($middleware, $request);

    expect($shouldSkip)->toBeTrue();
})->with('skipped paths');

dataset('non-skipped paths', [
    'dashboard',
    'profile',
    'reports/2025',
    'api/data',
]);

it('does not skip the SSO flow on protected paths', function (string $path) {
    $middleware = new SSOAutoLogin;
    $request = Request::create('/'.$path, 'GET');

    $shouldSkip = (new ReflectionClass($middleware))
        ->getMethod('shouldSkip')
        ->invoke($middleware, $request);

    expect($shouldSkip)->toBeFalse();
})->with('non-skipped paths');

it('passes through the request when the path is excluded', function () {
    $middleware = new SSOAutoLogin;
    $request = Request::create('/login', 'GET');

    $response = $middleware->handle($request, fn () => response('ok'));

    expect($response->getContent())->toBe('ok');
});
