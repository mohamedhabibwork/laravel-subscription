<?php

namespace HSubscription\LaravelSubscription\Enums;

enum FeatureType: string
{
    case Boolean = 'boolean';
    case Limit = 'limit';
    case Consumable = 'consumable';

    public function isBoolean(): bool
    {
        return $this === self::Boolean;
    }

    public function isLimit(): bool
    {
        return $this === self::Limit;
    }

    public function isConsumable(): bool
    {
        return $this === self::Consumable;
    }
}
