<?php

namespace Zefy\LaravelSSO;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Zefy\LaravelSSO\Exceptions\MissingConfigurationException;
use Zefy\SimpleSSO\SSOBroker;

class LaravelSSOBroker extends SSOBroker
{
    /**
     * Build a URL to an SSO server endpoint.
     */
    protected function generateCommandUrl(string $command, array $parameters = []): string
    {
        $query = empty($parameters) ? '' : '?'.http_build_query($parameters);

        return $this->ssoServerUrl.'/api/sso/'.$command.$query;
    }

    /**
     * @throws MissingConfigurationException
     */
    protected function setOptions(): void
    {
        $this->ssoServerUrl = config('laravel-sso.serverUrl');
        $this->brokerName = config('laravel-sso.brokerName');
        $this->brokerSecret = config('laravel-sso.brokerSecret');

        if (! $this->ssoServerUrl || ! $this->brokerName || ! $this->brokerSecret) {
            throw new MissingConfigurationException(
                'Missing SSO configuration. Set serverUrl, brokerName and brokerSecret.'
            );
        }
    }

    /**
     * Persist the broker token in a cookie. Triggers an attach when no token exists yet.
     */
    protected function saveToken(): void
    {
        if (! empty($this->token)) {
            return;
        }

        if ($this->token = Cookie::get($this->getCookieName())) {
            return;
        }

        $this->token = Str::random(40);
        Cookie::queue(Cookie::make($this->getCookieName(), $this->token, 60));

        $this->attach();
    }

    protected function deleteToken(): void
    {
        $this->token = null;
        Cookie::queue(Cookie::forget($this->getCookieName()));
    }

    /**
     * Make an HTTP request to the SSO server.
     */
    protected function makeRequest(string $method, string $command, array $parameters = []): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->getSessionId(),
        ];

        $body = match (strtoupper($method)) {
            'POST' => ['form_params' => $parameters],
            'GET' => ['query' => $parameters],
            default => [],
        };

        $response = (new Client)->request(
            $method,
            $this->generateCommandUrl($command),
            $body + ['headers' => $headers]
        );

        return json_decode($response->getBody(), true) ?? [];
    }

    /**
     * Redirect the client. Defaults to 303 so a POST request followed
     * by a redirect becomes a GET on the SSO server.
     */
    protected function redirect(string $url, array $parameters = [], int $httpResponseCode = 303): void
    {
        $query = '';
        if (! empty($parameters)) {
            $query = parse_url($url, PHP_URL_QUERY) ? '&' : '?';
            $query .= http_build_query($parameters);
        }

        app()->abort($httpResponseCode, '', ['Location' => $url.$query]);
    }

    protected function getCurrentUrl(): string
    {
        return url()->full();
    }

    /**
     * Cookie name used to persist the broker token. Includes the broker
     * name to avoid collisions when several brokers share a domain.
     */
    protected function getCookieName(): string
    {
        return 'sso_token_'.preg_replace('/[_\W]+/', '_', strtolower($this->brokerName));
    }
}
