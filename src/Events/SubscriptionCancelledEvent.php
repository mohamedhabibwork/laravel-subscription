<?php

namespace HSubscription\LaravelSubscription\Events;

use HSubscription\LaravelSubscription\Models\Subscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionCancelledEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Subscription $subscription
    ) {}
}
