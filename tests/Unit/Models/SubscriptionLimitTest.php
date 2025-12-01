<?php

use HSubscription\LaravelSubscription\Models\Feature;
use HSubscription\LaravelSubscription\Models\Subscription;
use HSubscription\LaravelSubscription\Models\SubscriptionLimit;

it('generates uuid on creation', function () {
    $limit = SubscriptionLimit::factory()->create();

    expect($limit->uuid)->not->toBeNull();
});

it('belongs to a subscription', function () {
    $subscription = Subscription::factory()->create();
    $limit = SubscriptionLimit::factory()->create(['subscription_id' => $subscription->id]);

    expect($limit->subscription)->toBeInstanceOf(Subscription::class)
        ->and($limit->subscription_id)->toBe($subscription->id);
});

it('belongs to a feature', function () {
    $feature = Feature::factory()->create();
    $limit = SubscriptionLimit::factory()->create(['feature_id' => $feature->id]);

    expect($limit->feature)->toBeInstanceOf(Feature::class)
        ->and($limit->feature_id)->toBe($feature->id);
});

it('can be hard limit type', function () {
    $limit = SubscriptionLimit::factory()->hard()->create();

    expect($limit->isHard())->toBeTrue()
        ->and($limit->isSoft())->toBeFalse();
});

it('can be soft limit type', function () {
    $limit = SubscriptionLimit::factory()->soft()->create();

    expect($limit->isSoft())->toBeTrue()
        ->and($limit->isHard())->toBeFalse();
});

it('can have custom limit and warning threshold', function () {
    $limit = SubscriptionLimit::factory()->create([
        'custom_limit' => 500,
        'warning_threshold' => 400,
    ]);

    expect($limit->custom_limit)->toBe(500)
        ->and($limit->warning_threshold)->toBe(400);
});
