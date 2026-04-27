<?php

use Illuminate\Support\Facades\Route;

it('registers SSO server routes when type is server', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->map(fn ($route) => $route->uri())
        ->filter(fn ($uri) => str_starts_with($uri, 'api/sso'))
        ->values()
        ->all();

    expect($routes)
        ->toContain('api/sso/login')
        ->toContain('api/sso/logout')
        ->toContain('api/sso/attach')
        ->toContain('api/sso/userInfo');
});

it('serves the attach endpoint over GET', function () {
    $routes = collect(Route::getRoutes()->get('GET'))
        ->keys()
        ->filter(fn ($uri) => str_starts_with($uri, 'api/sso'))
        ->all();

    expect($routes)
        ->toContain('api/sso/attach')
        ->toContain('api/sso/userInfo');
});

it('serves login and logout endpoints over POST', function () {
    $routes = collect(Route::getRoutes()->get('POST'))
        ->keys()
        ->filter(fn ($uri) => str_starts_with($uri, 'api/sso'))
        ->all();

    expect($routes)
        ->toContain('api/sso/login')
        ->toContain('api/sso/logout');
});
