<?php

namespace Zefy\LaravelSSO;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Zefy\LaravelSSO\Resources\UserResource;
use Zefy\SimpleSSO\SSOServer;

class LaravelSSOServer extends SSOServer
{
    /**
     * Redirect to the given URL.
     */
    protected function redirect(?string $url = null, array $parameters = [], int $httpResponseCode = 307)
    {
        if (! $url) {
            $url = urldecode(request()->get('return_url', ''));
        }

        $query = '';
        if (! empty($parameters)) {
            $query = parse_url($url, PHP_URL_QUERY) ? '&' : '?';
            $query .= http_build_query($parameters);
        }

        app()->abort($httpResponseCode, '', ['Location' => $url.$query]);
    }

    /**
     * Return a JSON response.
     */
    protected function returnJson(?array $response = null, int $httpResponseCode = 200)
    {
        return response()->json($response, $httpResponseCode);
    }

    /**
     * Authenticate using user credentials. After Auth::attempt() Laravel rotates
     * the session id, but we need to keep the broker-linked session id stable.
     */
    protected function authenticate(string $username, string $password): bool
    {
        if (! Auth::attempt(['email' => $username, 'password' => $password])) {
            return false;
        }

        $sessionId = $this->getBrokerSessionId();
        $savedSessionId = $this->getBrokerSessionData($sessionId);
        $this->startSession($savedSessionId);

        return true;
    }

    /**
     * Resolve a broker by name.
     */
    protected function getBrokerInfo(string $brokerId): ?object
    {
        try {
            return config('laravel-sso.brokersModel')::where('name', $brokerId)->firstOrFail();
        } catch (ModelNotFoundException) {
            return null;
        }
    }

    /**
     * Resolve a user by username (email).
     */
    protected function getUserInfo(string $username): ?object
    {
        try {
            return config('laravel-sso.usersModel')::where('email', $username)->firstOrFail();
        } catch (ModelNotFoundException) {
            return null;
        }
    }

    /**
     * Format the user payload returned to the broker.
     */
    protected function returnUserInfo($user): UserResource
    {
        return new UserResource($user);
    }

    /**
     * Extract the broker session id from the Authorization header.
     */
    protected function getBrokerSessionId(): ?string
    {
        $authorization = request()->header('Authorization');

        if ($authorization && str_starts_with($authorization, 'Bearer ')) {
            return substr($authorization, 7);
        }

        return null;
    }

    /**
     * Hook for starting the user session. The session middleware already
     * handles this, so nothing to do here.
     */
    protected function startUserSession(): void
    {
        //
    }

    protected function setSessionData(string $key, ?string $value = null): void
    {
        if ($value === null) {
            Session::forget($key);

            return;
        }

        Session::put($key, $value);
    }

    protected function getSessionData(string $key): ?string
    {
        if ($key === 'id') {
            return Session::getId();
        }

        return Session::get($key);
    }

    protected function startSession(string $sessionId): void
    {
        Session::setId($sessionId);
        Session::start();
    }

    protected function saveBrokerSessionData(string $brokerSessionId, string $sessionData): void
    {
        Cache::put('broker_session:'.$brokerSessionId, $sessionData, now()->addHour());
    }

    protected function getBrokerSessionData(string $brokerSessionId): ?string
    {
        return Cache::get('broker_session:'.$brokerSessionId);
    }
}
