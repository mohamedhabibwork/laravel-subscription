<?php

namespace HSubscription\LaravelSubscription\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class RequiresSubscription
{
    public function __construct(
        public string $subscriptionName = 'default',
        public bool $allowTrial = true
    ) {}
}
