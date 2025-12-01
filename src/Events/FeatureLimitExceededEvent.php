<?php

namespace HSubscription\LaravelSubscription\Events;

use HSubscription\LaravelSubscription\Models\Feature;
use HSubscription\LaravelSubscription\Models\Subscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FeatureLimitExceededEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public Feature $feature,
        public int $requestedAmount
    ) {}
}
