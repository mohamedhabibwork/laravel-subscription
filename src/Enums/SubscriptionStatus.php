<?php

namespace HSubscription\LaravelSubscription\Enums;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case OnTrial = 'on_trial';
    case PastDue = 'past_due';
    case Paused = 'paused';

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function isCancelled(): bool
    {
        return $this === self::Cancelled;
    }

    public function isExpired(): bool
    {
        return $this === self::Expired;
    }

    public function isOnTrial(): bool
    {
        return $this === self::OnTrial;
    }

    public function isPastDue(): bool
    {
        return $this === self::PastDue;
    }

    public function isPaused(): bool
    {
        return $this === self::Paused;
    }

    public function allowsAccess(): bool
    {
        return $this === self::Active || $this === self::OnTrial;
    }
}
