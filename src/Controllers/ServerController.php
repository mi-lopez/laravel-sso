<?php

namespace Zefy\LaravelSSO\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Zefy\LaravelSSO\LaravelSSOServer;
use Zefy\LaravelSSO\Resources\UserResource;

class ServerController extends BaseController
{
    public function attach(Request $request, LaravelSSOServer $server): void
    {
        $server->attach(
            $request->get('broker'),
            $request->get('token'),
            $request->get('checksum')
        );
    }

    public function login(Request $request, LaravelSSOServer $server): JsonResponse|UserResource
    {
        return $server->login(
            $request->get('username'),
            $request->get('password')
        );
    }

    public function logout(LaravelSSOServer $server): JsonResponse
    {
        return $server->logout();
    }

    public function userInfo(LaravelSSOServer $server): JsonResponse|UserResource
    {
        return $server->userInfo();
    }
}
