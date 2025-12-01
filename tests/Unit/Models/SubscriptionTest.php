<?php

use HSubscription\LaravelSubscription\Enums\SubscriptionStatus;
use HSubscription\LaravelSubscription\Models\Plan;
use HSubscription\LaravelSubscription\Models\Subscription;

it('generates uuid on creation', function () {
    $subscription = Subscription::factory()->create();

    expect($subscription->uuid)->not->toBeNull();
});

it('has polymorphic subscribable relationship', function () {
    $user = new class extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'users';
    };
    $user->id = 1;

    $subscription = Subscription::factory()->create([
        'subscribable_type' => get_class($user),
        'subscribable_id' => $user->id,
    ]);

    expect($subscription->subscribable_type)->toBe(get_class($user))
        ->and($subscription->subscribable_id)->toBe($user->id);
});

it('belongs to a plan', function () {
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create(['plan_id' => $plan->id]);

    expect($subscription->plan)->toBeInstanceOf(Plan::class)
        ->and($subscription->plan_id)->toBe($plan->id);
});

it('can check if active', function () {
    $active = Subscription::factory()->create(['status' => SubscriptionStatus::Active]);
    $inactive = Subscription::factory()->create(['status' => SubscriptionStatus::Cancelled]);

    expect($active->isActive())->toBeTrue()
        ->and($inactive->isActive())->toBeFalse();
});

it('can check if on trial', function () {
    $trial = Subscription::factory()->onTrial()->create();
    $active = Subscription::factory()->create(['status' => SubscriptionStatus::Active]);

    expect($trial->isOnTrial())->toBeTrue()
        ->and($active->isOnTrial())->toBeFalse();
});

it('can check if cancelled', function () {
    $cancelled = Subscription::factory()->cancelled()->create();
    $active = Subscription::factory()->create(['status' => SubscriptionStatus::Active]);

    expect($cancelled->isCancelled())->toBeTrue()
        ->and($active->isCancelled())->toBeFalse();
});

it('can scope active subscriptions', function () {
    Subscription::factory()->active()->create();
    Subscription::factory()->cancelled()->create();

    expect(Subscription::active()->count())->toBe(1);
});
