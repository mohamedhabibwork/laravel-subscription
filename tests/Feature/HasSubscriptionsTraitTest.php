<?php

use HSubscription\LaravelSubscription\Concerns\HasSubscriptions;
use HSubscription\LaravelSubscription\Enums\SubscriptionStatus;
use HSubscription\LaravelSubscription\Models\Feature;
use HSubscription\LaravelSubscription\Models\Module;
use HSubscription\LaravelSubscription\Models\Plan;
use HSubscription\LaravelSubscription\Models\Subscription;
use Illuminate\Database\Eloquent\Model;

beforeEach(function () {
    $this->user = new class extends Model
    {
        use HasSubscriptions;

        protected $table = 'users';

        public $timestamps = false;
    };
    $this->user->id = 1;
});

it('can subscribe to a plan', function () {
    $plan = Plan::factory()->create();

    $subscription = $this->user->subscribe($plan);

    expect($subscription)->toBeInstanceOf(Subscription::class)
        ->and($subscription->subscribable_type)->toBe(get_class($this->user))
        ->and($subscription->subscribable_id)->toBe($this->user->id)
        ->and($subscription->plan_id)->toBe($plan->id);
});

it('can check if subscribed to plan', function () {
    $plan = Plan::factory()->create();
    $this->user->subscribe($plan);

    expect($this->user->subscribedTo($plan))->toBeTrue();
});

it('can check if on trial', function () {
    $plan = Plan::factory()->create(['trial_days' => 7]);
    $this->user->subscribe($plan);

    expect($this->user->onTrial())->toBeTrue();
});

it('can check if has feature', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->boolean()->create();
    $plan->features()->attach($feature->id, ['value' => 1]);

    $this->user->subscribe($plan);

    expect($this->user->hasFeature($feature))->toBeTrue();
});

it('can check if has module', function () {
    $plan = Plan::factory()->create();
    $module = Module::factory()->create();
    $plan->modules()->attach($module->id, ['is_enabled' => true]);

    $subscription = $this->user->subscribe($plan);
    $subscription->moduleActivations()->create([
        'module_id' => $module->id,
        'is_active' => true,
        'activated_at' => now(),
    ]);

    expect($this->user->hasModule($module))->toBeTrue();
});

it('can consume feature', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->consumable()->create(['default_value' => 100]);
    $plan->features()->attach($feature->id, ['value' => 100]);

    $this->user->subscribe($plan);

    expect($this->user->consumeFeature($feature, 10))->toBeTrue()
        ->and($this->user->remainingFeatureUsage($feature))->toBe(90);
});

it('can get all subscriptions', function () {
    $plan1 = Plan::factory()->create();
    $plan2 = Plan::factory()->create();

    $this->user->subscribe($plan1, ['name' => 'main']);
    $this->user->subscribe($plan2, ['name' => 'secondary']);

    expect($this->user->subscriptions)->toHaveCount(2);
});

it('can get active subscriptions only', function () {
    $plan1 = Plan::factory()->create(['trial_days' => 0]);
    $plan2 = Plan::factory()->create(['trial_days' => 0]);

    $sub1 = $this->user->subscribe($plan1, ['name' => 'main']);
    $sub2 = $this->user->subscribe($plan2, ['name' => 'secondary']);
    $sub2->update(['status' => SubscriptionStatus::Cancelled]);

    expect($this->user->activeSubscriptions()->get())->toHaveCount(1);
});

it('can get subscription by name', function () {
    $plan1 = Plan::factory()->create();
    $plan2 = Plan::factory()->create();

    $this->user->subscribe($plan1, ['name' => 'main']);
    $this->user->subscribe($plan2, ['name' => 'secondary']);

    $mainSubscription = $this->user->subscription('main');
    $secondarySubscription = $this->user->subscription('secondary');

    expect($mainSubscription->name)->toBe('main')
        ->and($secondarySubscription->name)->toBe('secondary');
});

it('can check if cancelled', function () {
    $plan = Plan::factory()->create();
    $subscription = $this->user->subscribe($plan);
    $subscription->update(['status' => SubscriptionStatus::Cancelled, 'cancelled_at' => now()]);

    expect($this->user->cancelled())->toBeTrue();
});

it('can check if active', function () {
    $plan = Plan::factory()->create();
    $this->user->subscribe($plan);

    expect($this->user->active())->toBeTrue();
});

it('returns false for active when subscription is cancelled', function () {
    $plan = Plan::factory()->create();
    $subscription = $this->user->subscribe($plan);
    $subscription->update(['status' => SubscriptionStatus::Cancelled]);

    expect($this->user->active())->toBeFalse();
});

it('can check if can use feature with limit type', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->limit()->create(['default_value' => 100]);
    $plan->features()->attach($feature->id, ['value' => 100]);

    $this->user->subscribe($plan);

    expect($this->user->canUseFeature($feature, 50))->toBeTrue()
        ->and($this->user->canUseFeature($feature, 150))->toBeFalse();
});

it('can check if can use boolean feature', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->boolean()->create();
    $plan->features()->attach($feature->id, ['value' => 1]);

    $this->user->subscribe($plan);

    expect($this->user->canUseFeature($feature))->toBeTrue();
});

it('cannot consume feature beyond limit', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->consumable()->create(['default_value' => 100]);
    $plan->features()->attach($feature->id, ['value' => 100]);

    $this->user->subscribe($plan);

    expect($this->user->consumeFeature($feature, 150))->toBeFalse();
});

it('cannot consume boolean feature', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->boolean()->create();
    $plan->features()->attach($feature->id, ['value' => 1]);

    $this->user->subscribe($plan);

    // Boolean features don't have consumption, should return false
    expect($this->user->consumeFeature($feature, 1))->toBeFalse();
});

it('can get remaining feature usage', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->consumable()->create(['default_value' => 100]);
    $plan->features()->attach($feature->id, ['value' => 100]);

    $this->user->subscribe($plan);
    $this->user->consumeFeature($feature, 30);

    expect($this->user->remainingFeatureUsage($feature))->toBe(70);
});

it('returns 0 remaining usage when limit exceeded', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->consumable()->create(['default_value' => 100]);
    $plan->features()->attach($feature->id, ['value' => 100]);

    $this->user->subscribe($plan);
    $subscription = $this->user->subscription();
    $subscription->usage()->create([
        'feature_id' => $feature->id,
        'used' => 100,
        'limit' => 100,
        'valid_until' => now()->addMonth(),
    ]);

    expect($this->user->remainingFeatureUsage($feature))->toBe(0);
});

it('can get feature value', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->limit()->create(['default_value' => 50]);
    $plan->features()->attach($feature->id, ['value' => 200]);

    $this->user->subscribe($plan);

    expect($this->user->featureValue($feature))->toBe(200);
});

it('can get feature usage history', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->consumable()->create();
    $plan->features()->attach($feature->id, ['value' => 100]);

    $this->user->subscribe($plan);
    $subscription = $this->user->subscription();
    $subscription->usage()->create([
        'feature_id' => $feature->id,
        'used' => 50,
        'limit' => 100,
        'valid_until' => now()->addMonth(),
    ]);

    $history = $this->user->featureUsageHistory($feature);

    expect($history)->toHaveCount(1)
        ->and($history->first()->used)->toBe(50);
});

it('can get active modules', function () {
    $plan = Plan::factory()->create();
    $module = Module::factory()->create();
    $plan->modules()->attach($module->id, ['is_enabled' => true]);

    $subscription = $this->user->subscribe($plan);
    $subscription->moduleActivations()->create([
        'module_id' => $module->id,
        'is_active' => true,
        'activated_at' => now(),
    ]);

    $activeModules = $this->user->activeModules();

    expect($activeModules)->toHaveCount(1)
        ->and($activeModules->first()->id)->toBe($module->id);
});

it('can get module features', function () {
    $plan = Plan::factory()->create();
    $module = Module::factory()->create();
    $feature = Feature::factory()->create(['module_id' => $module->id]);
    $plan->modules()->attach($module->id, ['is_enabled' => true]);

    $subscription = $this->user->subscribe($plan);
    $subscription->moduleActivations()->create([
        'module_id' => $module->id,
        'is_active' => true,
        'activated_at' => now(),
    ]);

    $moduleFeatures = $this->user->moduleFeatures($module);

    expect($moduleFeatures)->toHaveCount(1)
        ->and($moduleFeatures->first()->id)->toBe($feature->id);
});

it('can activate module', function () {
    $plan = Plan::factory()->create();
    $module = Module::factory()->create();
    $plan->modules()->attach($module->id, ['is_enabled' => true]);

    $this->user->subscribe($plan);

    expect($this->user->activateModule($module))->toBeTrue()
        ->and($this->user->hasModule($module))->toBeTrue();
});

it('can deactivate module', function () {
    $plan = Plan::factory()->create();
    $module = Module::factory()->create();
    $plan->modules()->attach($module->id, ['is_enabled' => true]);

    $subscription = $this->user->subscribe($plan);
    $subscription->moduleActivations()->create([
        'module_id' => $module->id,
        'is_active' => true,
        'activated_at' => now(),
    ]);

    expect($this->user->deactivateModule($module))->toBeTrue()
        ->and($this->user->hasModule($module))->toBeFalse();
});

it('can check limit', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->limit()->create(['default_value' => 100]);
    $plan->features()->attach($feature->id, ['value' => 100]);

    $this->user->subscribe($plan);

    expect($this->user->checkLimit($feature))->toBeTrue();
});

it('can set custom limit', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->limit()->create(['default_value' => 100]);
    $plan->features()->attach($feature->id, ['value' => 100]);

    $this->user->subscribe($plan);

    expect($this->user->setCustomLimit($feature, 200))->toBeTrue()
        ->and($this->user->featureValue($feature))->toBe(200);
});

it('can reset limits', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->consumable()->create(['default_value' => 100]);
    $plan->features()->attach($feature->id, ['value' => 100]);

    $this->user->subscribe($plan);
    $this->user->consumeFeature($feature, 50);

    expect($this->user->resetLimits())->toBeTrue()
        ->and($this->user->remainingFeatureUsage($feature))->toBe(100);
});

it('can get limit status', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->consumable()->create(['default_value' => 100]);
    $plan->features()->attach($feature->id, ['value' => 100]);

    $this->user->subscribe($plan);
    $this->user->consumeFeature($feature, 30);

    $status = $this->user->limitStatus($feature);

    expect($status)->toHaveKeys(['limit', 'used', 'remaining'])
        ->and($status['limit'])->toBe(100)
        ->and($status['used'])->toBe(30)
        ->and($status['remaining'])->toBe(70);
});

it('returns false when no subscription exists', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->boolean()->create();
    $plan->features()->attach($feature->id, ['value' => 1]);

    expect($this->user->hasFeature($feature))->toBeFalse()
        ->and($this->user->active())->toBeFalse()
        ->and($this->user->onTrial())->toBeFalse()
        ->and($this->user->cancelled())->toBeFalse();
});

it('returns false for feature access when subscription expired', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->boolean()->create();
    $plan->features()->attach($feature->id, ['value' => 1]);

    $subscription = $this->user->subscribe($plan);
    $subscription->update(['status' => SubscriptionStatus::Expired, 'ends_at' => now()->subDay()]);

    expect($this->user->hasFeature($feature))->toBeFalse();
});

it('returns false for feature access when subscription cancelled', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->boolean()->create();
    $plan->features()->attach($feature->id, ['value' => 1]);

    $subscription = $this->user->subscribe($plan);
    $subscription->update(['status' => SubscriptionStatus::Cancelled]);

    expect($this->user->hasFeature($feature))->toBeFalse();
});
