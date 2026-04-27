# Laravel SSO

[![Tests](https://github.com/mi-lopez/laravel-sso/actions/workflows/tests.yml/badge.svg)](https://github.com/mi-lopez/laravel-sso/actions/workflows/tests.yml)
[![Lint](https://github.com/mi-lopez/laravel-sso/actions/workflows/lint.yml/badge.svg)](https://github.com/mi-lopez/laravel-sso/actions/workflows/lint.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/mi-lopez/laravel-sso.svg)](https://packagist.org/packages/mi-lopez/laravel-sso)
[![Total Downloads](https://img.shields.io/packagist/dt/mi-lopez/laravel-sso.svg)](https://packagist.org/packages/mi-lopez/laravel-sso)
[![License](https://img.shields.io/packagist/l/mi-lopez/laravel-sso.svg)](LICENSE.md)

Single Sign-On (SSO) integration for Laravel. One central server authenticates users; multiple broker apps share that login session. Based on [zefy/php-simple-sso](https://github.com/zefy/php-simple-sso).

## Version Compatibility

| Package | Laravel | PHP    | Branch                                                  |
|---------|---------|--------|---------------------------------------------------------|
| 8.x     | 8.x     | 7.4+   | [8.x](https://github.com/mi-lopez/laravel-sso/tree/8.x) |
| 11.x    | 11.x    | 8.2+   | [11.x](https://github.com/mi-lopez/laravel-sso/tree/11.x) |

## Concepts

- **Server** — the central authentication app. Stores credentials and issues sessions.
- **Broker** — a downstream app that delegates login to the server.
- **Token** — a per-broker, per-user value stored in a cookie that links the broker to a server session.

## How it works

1. A user visits a broker. The broker generates a random token and asks the server to attach it to the user's server session.
2. When the user submits credentials, the broker forwards them to the server. On success, the server marks the linked session as authenticated.
3. The broker (and any other broker) can now ask the server "who is logged in?" using its token, and the server returns the user — without re-prompting for credentials.

## Installation

```bash
composer require mi-lopez/laravel-sso
```

Publish the config:

```bash
php artisan vendor:publish --provider="Zefy\LaravelSSO\SSOServiceProvider"
```

This creates `config/laravel-sso.php`. Set `type` to either `server` or `broker` depending on the role of the application.

---

## Server Setup

### 1. Mark the app as the server

In `config/laravel-sso.php`:

```php
'type' => 'server',
```

### 2. Run the migrations

The package ships with two migrations (`brokers` and `broker_user`). Run them:

```bash
php artisan migrate
```

### 3. Enable sessions on the SSO API routes

The server endpoints (`/api/sso/*`) need access to sessions. In `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->api(prepend: [
        \Illuminate\Session\Middleware\StartSession::class,
    ]);
})
```

### 4. Register a broker

For each broker app you plan to run, generate a name and secret:

```bash
php artisan sso:broker:create my-broker
```

The command prints the secret. Copy it — the broker app needs it.

### 5. (Optional) Customize fields returned to brokers

`config/laravel-sso.php` lets you choose which user attributes are sent back. Defaults to `id` only:

```php
'userFields' => [
    'id'    => 'id',
    'email' => 'email',
    'name'  => 'name',
],
```

---

## Broker Setup

### 1. Mark the app as a broker

In `config/laravel-sso.php`:

```php
'type' => 'broker',
```

### 2. Configure the connection to the server

In `.env`:

```env
SSO_SERVER_URL=https://sso.example.com
SSO_BROKER_NAME=my-broker
SSO_BROKER_SECRET=<secret-printed-by-sso:broker:create>
```

### 3. Register the auto-login middleware

`SSOAutoLogin` must run **before** the `auth` middleware so it can log the user in transparently. Use `prependToPriorityList` in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->prependToPriorityList(
        before: \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
        prepend: \Zefy\LaravelSSO\Middleware\SSOAutoLogin::class,
    );
})
```

Then attach the middleware to your protected routes (typically alongside `auth`):

```php
use Zefy\LaravelSSO\Middleware\SSOAutoLogin;

Route::middleware([SSOAutoLogin::class, 'auth'])->group(function () {
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');
    // ...
});
```

> **Why a priority entry?** Laravel's default priority list places `Authenticate` near the end, which means `auth` would otherwise short-circuit a guest with a redirect to `/login` before `SSOAutoLogin` gets a chance to log them in via SSO. Pinning `SSOAutoLogin` before `AuthenticatesRequests` (the contract `auth` implements) fixes the order without copying the whole priority list.

### 4. Forward login and logout to the SSO server

You need to override the login form controller so credentials go through the broker. With **Laravel Breeze**, replace `app/Http/Controllers/Auth/AuthenticatedSessionController.php` with:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Zefy\LaravelSSO\LaravelSSOBroker;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        // Ensure the broker token cookie exists before the user submits the form,
        // so the POST hits the SSO server with a valid attached session.
        new LaravelSSOBroker;

        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $broker = new LaravelSSOBroker;

        if (! $broker->login($request->input('email'), $request->input('password'))) {
            return back()->withErrors(['email' => __('auth.failed')])->onlyInput('email');
        }

        $userInfo = $broker->getUserInfo();

        if (! empty($userInfo['data']['id'])) {
            auth()->loginUsingId($userInfo['data']['id']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        (new LaravelSSOBroker)->logout();

        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
```

For other auth scaffolding (Jetstream, Fortify, custom): wherever you handle the login POST, replace the local `Auth::attempt` with `$broker->login(...)` and call `$broker->logout()` on logout.

### 5. (Optional) Mirror users locally

When the broker calls `auth()->loginUsingId($id)`, Laravel looks for the user in the **broker's** database. The simplest setup is to keep the same `users` table on each broker as on the server (same id, same email). If you don't want that, you can:

- Replace the call with a custom resolver that creates/updates a local user from the SSO payload, or
- Use a custom guard backed by the SSO response.

---

## Artisan Commands

| Command                       | Description           |
|-------------------------------|-----------------------|
| `sso:broker:create {name}`    | Create a new broker.  |
| `sso:broker:delete {name}`    | Delete a broker.      |
| `sso:broker:list`             | List all brokers.     |

---

## Configuration Reference

`config/laravel-sso.php`:

| Key                  | Default                  | Description                                                         |
|----------------------|--------------------------|---------------------------------------------------------------------|
| `type`               | `server`                 | `server` or `broker`.                                               |
| `usersModel`         | `App\Models\User::class` | Eloquent model used by the server to look up users.                 |
| `brokersModel`       | `Broker::class`          | Eloquent model used by the server to look up brokers.               |
| `brokersUserModel`   | `BrokerUser::class`      | Pivot model linking users and brokers (optional, for custom flows). |
| `brokersTable`       | `brokers`                | Table name backing `brokersModel`.                                  |
| `brokerUserTable`    | `broker_user`            | Table name backing `brokersUserModel`.                              |
| `userFields`         | `['id' => 'id']`         | Map of payload-key → user-column for fields sent to brokers.        |
| `serverUrl`          | env `SSO_SERVER_URL`     | (broker) URL of the SSO server.                                     |
| `brokerName`         | env `SSO_BROKER_NAME`    | (broker) Broker name registered on the server.                      |
| `brokerSecret`       | env `SSO_BROKER_SECRET`  | (broker) Secret printed by `sso:broker:create`.                     |

---

## Testing

```bash
composer test
```

## Code Style

```bash
composer lint
```

---

## License

MIT. See [LICENSE.md](LICENSE.md).
