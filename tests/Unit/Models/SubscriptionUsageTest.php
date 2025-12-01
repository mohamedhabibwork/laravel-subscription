<?php

use HSubscription\LaravelSubscription\Models\Feature;
use HSubscription\LaravelSubscription\Models\Subscription;
use HSubscription\LaravelSubscription\Models\SubscriptionUsage;

it('generates uuid on creation', function () {
    $usage = SubscriptionUsage::factory()->create();

    expect($usage->uuid)->not->toBeNull();
});

it('belongs to a subscription', function () {
    $subscription = Subscription::factory()->create();
    $usage = SubscriptionUsage::factory()->create(['subscription_id' => $subscription->id]);

    expect($usage->subscription)->toBeInstanceOf(Subscription::class)
        ->and($usage->subscription_id)->toBe($subscription->id);
});

it('belongs to a feature', function () {
    $feature = Feature::factory()->create();
    $usage = SubscriptionUsage::factory()->create(['feature_id' => $feature->id]);

    expect($usage->feature)->toBeInstanceOf(Feature::class)
        ->and($usage->feature_id)->toBe($feature->id);
});

it('can scope valid usage records', function () {
    SubscriptionUsage::factory()->create(['valid_until' => now()->addDay()]);
    SubscriptionUsage::factory()->create(['valid_until' => null]);
    SubscriptionUsage::factory()->expired()->create();

    expect(SubscriptionUsage::valid()->count())->toBe(2);
});

it('can scope expired usage records', function () {
    SubscriptionUsage::factory()->create(['valid_until' => now()->addDay()]);
    SubscriptionUsage::factory()->expired()->create();

    expect(SubscriptionUsage::expired()->count())->toBe(1);
});

it('tracks used amount and limit', function () {
    $usage = SubscriptionUsage::factory()->create([
        'used' => 50,
        'limit' => 100,
    ]);

    expect($usage->used)->toBe(50)
        ->and($usage->limit)->toBe(100);
});
