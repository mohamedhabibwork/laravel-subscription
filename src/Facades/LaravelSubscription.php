<?php

namespace HSubscription\LaravelSubscription\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \HSubscription\LaravelSubscription\LaravelSubscription
 */
class LaravelSubscription extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \HSubscription\LaravelSubscription\LaravelSubscription::class;
    }
}
