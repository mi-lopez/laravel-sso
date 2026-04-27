<?php

namespace Zefy\LaravelSSO\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateBroker extends Command
{
    protected $signature = 'sso:broker:create {name}';

    protected $description = 'Create a new SSO broker.';

    public function handle(): int
    {
        $brokerClass = config('laravel-sso.brokersModel');

        $broker = $brokerClass::create([
            'name' => $this->argument('name'),
            'secret' => Str::random(40),
        ]);

        $this->info('Broker `'.$broker->name.'` created successfully.');
        $this->line('Secret: '.$broker->secret);

        return self::SUCCESS;
    }
}
