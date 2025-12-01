<?php

namespace HSubscription\LaravelSubscription\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ConfigKey
{
    public function __construct(
        public string $key
    ) {}
}
