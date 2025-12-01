<?php

namespace HSubscription\LaravelSubscription\Enums;

enum PlanChangeType: string
{
    case Upgrade = 'upgrade';
    case Downgrade = 'downgrade';
    case Switch = 'switch';

    public function isUpgrade(): bool
    {
        return $this === self::Upgrade;
    }

    public function isDowngrade(): bool
    {
        return $this === self::Downgrade;
    }

    public function isSwitch(): bool
    {
        return $this === self::Switch;
    }
}
