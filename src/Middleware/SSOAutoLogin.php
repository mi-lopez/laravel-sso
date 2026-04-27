<?php

namespace Zefy\LaravelSSO\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Zefy\LaravelSSO\LaravelSSOBroker;

class SSOAutoLogin
{
    /**
     * Paths that should not trigger the auto-login flow.
     */
    protected array $except = [
        'login',
        'logout',
        'register',
        'forgot-password',
        'reset-password',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $broker = new LaravelSSOBroker;

        try {
            $response = $broker->getUserInfo();
        } catch (\Throwable) {
            return $next($request);
        }

        // Server says no authenticated user, but we have a local session — sync logout.
        if (! isset($response['data']) && ! auth()->guest()) {
            return $this->logout($request);
        }

        // Stale broker token: clear the cookie so the next request re-attaches.
        if (isset($response['error']) && str_contains($response['error'], 'no saved session data')) {
            return $this->clearSSOCookie($request);
        }

        // Server has an authenticated user — sync login locally if needed.
        if (isset($response['data']) && (auth()->guest() || auth()->id() != $response['data']['id'])) {
            auth()->loginUsingId($response['data']['id']);
        }

        return $next($request);
    }

    protected function shouldSkip(Request $request): bool
    {
        foreach ($this->except as $path) {
            if ($request->is($path) || $request->is($path.'/*')) {
                return true;
            }
        }

        return false;
    }

    protected function clearSSOCookie(Request $request): Response
    {
        $cookieName = 'sso_token_'.config('laravel-sso.brokerName');

        return redirect($request->fullUrl())->withCookie(cookie()->forget($cookieName));
    }

    protected function logout(Request $request): Response
    {
        auth()->logout();

        return redirect($request->fullUrl());
    }
}
