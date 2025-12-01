<?php

use HSubscription\LaravelSubscription\Models\Feature;
use HSubscription\LaravelSubscription\Models\Module;
use HSubscription\LaravelSubscription\Models\Plan;
use HSubscription\LaravelSubscription\Models\Subscription;

it('generates uuid on creation', function () {
    $plan = Plan::factory()->create();

    expect($plan->uuid)->not->toBeNull()
        ->and($plan->uuid)->toBeString();
});

it('has many subscriptions', function () {
    $plan = Plan::factory()->create();
    Subscription::factory()->count(3)->create(['plan_id' => $plan->id]);

    expect($plan->subscriptions)->toHaveCount(3);
});

it('belongs to many features', function () {
    $plan = Plan::factory()->create();
    $features = Feature::factory()->count(3)->create();

    $plan->features()->attach($features->pluck('id')->toArray(), ['value' => 100]);

    expect($plan->features)->toHaveCount(3);
});

it('belongs to many modules', function () {
    $plan = Plan::factory()->create();
    $modules = Module::factory()->count(2)->create();

    $plan->modules()->attach($modules->pluck('id')->toArray(), ['is_enabled' => true]);

    expect($plan->modules)->toHaveCount(2);
});

it('can scope active plans', function () {
    Plan::factory()->active()->create();
    Plan::factory()->inactive()->create();

    expect(Plan::active()->count())->toBe(1);
});

it('can scope by tier', function () {
    Plan::factory()->create(['tier' => 'personal']);
    Plan::factory()->create(['tier' => 'business']);

    expect(Plan::byTier('personal')->count())->toBe(1);
});
