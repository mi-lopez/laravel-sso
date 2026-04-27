<?php

namespace Zefy\LaravelSSO\Commands;

use Illuminate\Console\Command;

class ListBrokers extends Command
{
    protected $signature = 'sso:broker:list';

    protected $description = 'List all SSO brokers.';

    public function handle(): int
    {
        $brokerClass = config('laravel-sso.brokersModel');
        $brokers = $brokerClass::all(['id', 'name', 'secret'])->toArray();

        $this->table(['ID', 'Name', 'Secret'], $brokers);

        return self::SUCCESS;
    }
}
