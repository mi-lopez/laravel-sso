<?php

namespace Zefy\LaravelSSO\Commands;

use Illuminate\Console\Command;

class DeleteBroker extends Command
{
    protected $signature = 'sso:broker:delete {name}';

    protected $description = 'Delete an SSO broker by name.';

    public function handle(): int
    {
        $brokerClass = config('laravel-sso.brokersModel');
        $broker = $brokerClass::where('name', $this->argument('name'))->first();

        if (! $broker) {
            $this->error('Broker `'.$this->argument('name').'` not found.');

            return self::FAILURE;
        }

        $broker->delete();

        $this->info('Broker `'.$this->argument('name').'` deleted successfully.');

        return self::SUCCESS;
    }
}
