<?php

namespace HSubscription\LaravelSubscription\Commands;

use Illuminate\Console\Command;

class LaravelSubscriptionCommand extends Command
{
    public $signature = 'laravel-subscription';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
