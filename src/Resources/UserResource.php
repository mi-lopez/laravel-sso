<?php

namespace Zefy\LaravelSSO\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $fields = [];
        foreach (config('laravel-sso.userFields') as $key => $value) {
            $fields[$key] = $this->{$value};
        }

        return $fields;
    }
}
