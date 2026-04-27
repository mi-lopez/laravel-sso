<?php

use Illuminate\Http\Request;
use Zefy\LaravelSSO\Resources\UserResource;

it('transforms user fields from config', function () {
    config()->set('laravel-sso.userFields', ['id' => 'id', 'email' => 'email']);

    $user = new class
    {
        public int $id = 1;

        public string $email = 'test@example.com';
    };

    $resource = new UserResource($user);
    $result = $resource->toArray(Request::create('/'));

    expect($result)->toBe(['id' => 1, 'email' => 'test@example.com']);
});

it('only exposes fields defined in config', function () {
    config()->set('laravel-sso.userFields', ['id' => 'id']);

    $user = new class
    {
        public int $id = 42;

        public string $email = 'hidden@example.com';
    };

    $resource = new UserResource($user);
    $result = $resource->toArray(Request::create('/'));

    expect($result)->toHaveKey('id', 42)
        ->and($result)->not->toHaveKey('email');
});
