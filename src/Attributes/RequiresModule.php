<?php

namespace HSubscription\LaravelSubscription\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class RequiresModule
{
    public function __construct(
        public string $module,
        public string $subscriptionName = 'default'
    ) {}
}
