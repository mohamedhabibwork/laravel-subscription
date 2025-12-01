<?php

namespace HSubscription\LaravelSubscription\Enums;

enum BillingInterval: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function isDaily(): bool
    {
        return $this === self::Daily;
    }

    public function isWeekly(): bool
    {
        return $this === self::Weekly;
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
