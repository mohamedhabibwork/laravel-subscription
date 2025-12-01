<?php

namespace HSubscription\LaravelSubscription\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ConfigDefault
{
    public function __construct(
        public mixed $value
    ) {}
}
