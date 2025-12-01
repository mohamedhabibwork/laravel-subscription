<?php

namespace HSubscription\LaravelSubscription\Enums;

enum LimitType: string
{
    case Hard = 'hard';
    case Soft = 'soft';

    public function isHard(): bool
    {
        return $this === self::Hard;
    }

    public function isSoft(): bool
    {
        return $this === self::Soft;
    }
}
