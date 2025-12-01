<?php

namespace HSubscription\LaravelSubscription\Events;

use HSubscription\LaravelSubscription\Models\Plan;
use HSubscription\LaravelSubscription\Models\Subscription;
use HSubscription\LaravelSubscription\Models\SubscriptionChange;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlanChangedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public Plan $oldPlan,
        public Plan $newPlan,
        public SubscriptionChange $change
    ) {}
}
