<?php

use HSubscription\LaravelSubscription\Enums\PlanChangeType;
use HSubscription\LaravelSubscription\Models\Plan;
use HSubscription\LaravelSubscription\Models\Subscription;
use HSubscription\LaravelSubscription\Models\SubscriptionChange;

it('generates uuid on creation', function () {
    $change = SubscriptionChange::factory()->create();

    expect($change->uuid)->not->toBeNull();
});

it('belongs to a subscription', function () {
    $subscription = Subscription::factory()->create();
    $change = SubscriptionChange::factory()->create(['subscription_id' => $subscription->id]);

    expect($change->subscription)->toBeInstanceOf(Subscription::class)
        ->and($change->subscription_id)->toBe($subscription->id);
});

it('has from and to plans', function () {
    $fromPlan = Plan::factory()->create();
    $toPlan = Plan::factory()->create();
    $change = SubscriptionChange::factory()->create([
        'from_plan_id' => $fromPlan->id,
        'to_plan_id' => $toPlan->id,
    ]);

    expect($change->fromPlan)->toBeInstanceOf(Plan::class)
        ->and($change->toPlan)->toBeInstanceOf(Plan::class)
        ->and($change->from_plan_id)->toBe($fromPlan->id)
        ->and($change->to_plan_id)->toBe($toPlan->id);
});

it('can be upgrade type', function () {
    $change = SubscriptionChange::factory()->upgrade()->create();

    expect($change->isUpgrade())->toBeTrue()
        ->and($change->isDowngrade())->toBeFalse()
        ->and($change->isSwitch())->toBeFalse();
});

it('can be downgrade type', function () {
    $change = SubscriptionChange::factory()->downgrade()->create();

    expect($change->isDowngrade())->toBeTrue()
        ->and($change->isUpgrade())->toBeFalse();
});

it('can be switch type', function () {
    $change = SubscriptionChange::factory()->create(['change_type' => PlanChangeType::Switch]);

    expect($change->isSwitch())->toBeTrue();
});

it('can be immediate or scheduled', function () {
    $immediate = SubscriptionChange::factory()->create(['is_immediate' => true]);
    $scheduled = SubscriptionChange::factory()->scheduled()->create();

    expect($immediate->is_immediate)->toBeTrue()
        ->and($scheduled->is_immediate)->toBeFalse()
        ->and($scheduled->scheduled_for)->not->toBeNull();
});

it('can scope applied changes', function () {
    SubscriptionChange::factory()->create(['applied_at' => now()]);
    SubscriptionChange::factory()->pending()->create();

    expect(SubscriptionChange::applied()->count())->toBe(1);
});

it('can scope pending changes', function () {
    SubscriptionChange::factory()->create(['applied_at' => now()]);
    SubscriptionChange::factory()->pending()->create();

    expect(SubscriptionChange::pending()->count())->toBe(1);
});

it('can have proration amount', function () {
    $change = SubscriptionChange::factory()->create([
        'proration_amount' => 25.50,
    ]);

    expect($change->proration_amount)->toBe(25.50);
});
