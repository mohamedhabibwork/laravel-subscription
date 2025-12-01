<?php

use HSubscription\LaravelSubscription\Concerns\HasSubscriptions;
use HSubscription\LaravelSubscription\Enums\PlanChangeType;
use HSubscription\LaravelSubscription\Enums\ResetPeriod;
use HSubscription\LaravelSubscription\Enums\SubscriptionStatus;
use HSubscription\LaravelSubscription\Models\Feature;
use HSubscription\LaravelSubscription\Models\Module;
use HSubscription\LaravelSubscription\Models\Plan;
use HSubscription\LaravelSubscription\Services\FeatureService;
use HSubscription\LaravelSubscription\Services\ModuleService;
use HSubscription\LaravelSubscription\Services\SubscriptionService;
use Illuminate\Database\Eloquent\Model;

beforeEach(function () {
    $this->user = new class extends Model
    {
        use HasSubscriptions;

        protected $table = 'users';

        public $timestamps = false;
    };
    $this->user->id = 1;

    $this->subscriptionService = app(SubscriptionService::class);
    $this->featureService = app(FeatureService::class);
    $this->moduleService = app(ModuleService::class);
});

it('completes full subscription lifecycle', function () {
    $plan = Plan::factory()->create(['trial_days' => 7]);

    // Create subscription with trial
    $subscription = $this->subscriptionService->create($this->user, $plan);
    expect($subscription->status)->toBe(SubscriptionStatus::OnTrial);

    // Convert trial to paid
    $subscription = $this->subscriptionService->convertTrialToPaid($subscription);
    expect($subscription->status)->toBe(SubscriptionStatus::Active);

    // Cancel subscription
    $subscription = $this->subscriptionService->cancel($subscription, false);
    expect($subscription->status)->toBe(SubscriptionStatus::Cancelled);

    // Resume subscription
    $subscription = $this->subscriptionService->resume($subscription);
    expect($subscription->status)->toBe(SubscriptionStatus::Active);
});

it('handles plan upgrade flow', function () {
    $basicPlan = Plan::factory()->create(['price' => 10.00, 'name' => 'Basic']);
    $premiumPlan = Plan::factory()->create(['price' => 20.00, 'name' => 'Premium']);

    $basicFeature = Feature::factory()->limit()->create(['default_value' => 100]);
    $premiumFeature = Feature::factory()->limit()->create(['default_value' => 500]);

    $basicPlan->features()->attach($basicFeature->id, ['value' => 100]);
    $premiumPlan->features()->attach($premiumFeature->id, ['value' => 500]);
    $premiumPlan->features()->attach($basicFeature->id, ['value' => 100]);

    $subscription = $this->subscriptionService->create($this->user, $basicPlan);

    // Upgrade to premium
    $change = $this->subscriptionService->changePlan($subscription, $premiumPlan);

    expect($change->change_type)->toBe(PlanChangeType::Upgrade)
        ->and($subscription->fresh()->plan_id)->toBe($premiumPlan->id)
        ->and($this->user->hasFeature($premiumFeature))->toBeTrue();
});

it('handles plan downgrade flow', function () {
    $premiumPlan = Plan::factory()->create(['price' => 20.00]);
    $basicPlan = Plan::factory()->create(['price' => 10.00]);

    $subscription = $this->subscriptionService->create($this->user, $premiumPlan);

    // Downgrade to basic
    $change = $this->subscriptionService->changePlan($subscription, $basicPlan);

    expect($change->change_type)->toBe(PlanChangeType::Downgrade)
        ->and($subscription->fresh()->plan_id)->toBe($basicPlan->id);
});

it('tracks feature consumption across billing cycles', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->consumable()->create([
        'default_value' => 100,
        'reset_period' => ResetPeriod::Monthly,
    ]);
    $plan->features()->attach($feature->id, ['value' => 100]);

    $subscription = $this->user->subscribe($plan);

    // Consume some usage
    $this->featureService->consume($this->user, $feature, 50);
    expect($this->featureService->getRemainingUsage($subscription, $feature))->toBe(50);

    // Reset usage
    $this->featureService->resetUsage($subscription, $feature);
    expect($this->featureService->getRemainingUsage($subscription, $feature))->toBe(100);
});

it('handles module activation workflow', function () {
    $plan = Plan::factory()->create();
    $module = Module::factory()->create();
    $feature = Feature::factory()->boolean()->create(['module_id' => $module->id]);
    $plan->modules()->attach($module->id, ['is_enabled' => true]);

    $subscription = $this->user->subscribe($plan);

    // Activate module
    $this->moduleService->activate($subscription, $module);
    expect($this->user->hasModule($module))->toBeTrue();

    // Get module features
    $features = $this->moduleService->getModuleFeatures($subscription, $module);
    expect($features)->toHaveCount(1);

    // Deactivate module
    $this->moduleService->deactivate($subscription, $module);
    expect($this->user->hasModule($module))->toBeFalse();
});

it('supports multiple subscriptions per subscriber', function () {
    $plan1 = Plan::factory()->create(['name' => 'Plan 1']);
    $plan2 = Plan::factory()->create(['name' => 'Plan 2']);

    $this->user->subscribe($plan1, ['name' => 'main']);
    $this->user->subscribe($plan2, ['name' => 'secondary']);

    expect($this->user->subscriptions)->toHaveCount(2)
        ->and($this->user->subscription('main')->plan_id)->toBe($plan1->id)
        ->and($this->user->subscription('secondary')->plan_id)->toBe($plan2->id);
});

it('supports polymorphic subscriber relationships', function () {
    $team = new class extends Model
    {
        use HasSubscriptions;

        protected $table = 'teams';

        public $timestamps = false;
    };
    $team->id = 1;

    $plan = Plan::factory()->create();
    $subscription = $team->subscribe($plan);

    expect($subscription->subscribable_type)->toBe(get_class($team))
        ->and($subscription->subscribable_id)->toBe($team->id);
});
