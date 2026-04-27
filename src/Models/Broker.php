<?php

namespace Zefy\LaravelSSO\Models;

use Illuminate\Database\Eloquent\Model;

class Broker extends Model
{
    protected $fillable = ['name', 'secret'];

    public function getTable(): string
    {
        return config('laravel-sso.brokersTable', 'brokers');
    }
}
