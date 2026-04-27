<?php

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Zefy\LaravelSSO\Models\Broker;
use Zefy\LaravelSSO\Models\BrokerUser;
use Zefy\LaravelSSO\SSOServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [SSOServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'pgsql');
        $app['config']->set('database.connections.pgsql', [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel_sso_test'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
        ]);

        $app['config']->set('laravel-sso.type', 'server');
        $app['config']->set('laravel-sso.usersModel', TestUser::class);
        $app['config']->set('laravel-sso.brokersModel', Broker::class);
        $app['config']->set('laravel-sso.brokersUserModel', BrokerUser::class);
        $app['config']->set('laravel-sso.brokersTable', 'brokers');
        $app['config']->set('laravel-sso.brokerUserTable', 'broker_user');
        $app['config']->set('laravel-sso.userFields', ['id' => 'id']);
    }

    protected function defineDatabaseMigrations(): void
    {
        // The package depends on a `users` table that the host app would normally provide.
        // Create a minimal stand-in so the broker_user FK can be satisfied.
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('email')->unique();
                $table->string('password')->nullable();
                $table->timestamps();
            });
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}

class TestUser extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];
}
