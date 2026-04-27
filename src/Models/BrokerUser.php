<?php

namespace Zefy\LaravelSSO\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BrokerUser extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'broker_id'];

    public function getTable(): string
    {
        return config('laravel-sso.brokerUserTable', 'broker_user');
    }
}
