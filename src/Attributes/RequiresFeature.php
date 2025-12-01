<?php

namespace HSubscription\LaravelSubscription\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class RequiresFeature
{
    public function __construct(
        public string $feature,
        public string $subscriptionName = 'default'
    ) {}
}
