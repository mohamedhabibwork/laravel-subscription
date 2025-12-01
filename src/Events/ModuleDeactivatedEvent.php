<?php

namespace HSubscription\LaravelSubscription\Events;

use HSubscription\LaravelSubscription\Models\Module;
use HSubscription\LaravelSubscription\Models\ModuleActivation;
use HSubscription\LaravelSubscription\Models\Subscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ModuleDeactivatedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public Module $module,
        public ModuleActivation $activation
    ) {}
}
