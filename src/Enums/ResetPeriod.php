<?php

namespace HSubscription\LaravelSubscription\Enums;

enum ResetPeriod: string
{
    case Never = 'never';
    case Daily = 'daily';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function isNever(): bool
    {
        return $this === self::Never;
    }

    public function isDaily(): bool
    {
        return $this === self::Daily;
    }

    public function isMonthly(): bool
    {
        return $this === self::Monthly;
    }

    public function isYearly(): bool
    {
        return $this === self::Yearly;
    }
}
