<?php

use HSubscription\LaravelSubscription\Concerns\HasSubscriptions;
use HSubscription\LaravelSubscription\Events\FeatureLimitExceededEvent;
use HSubscription\LaravelSubscription\Events\FeatureLimitReachedEvent;
use HSubscription\LaravelSubscription\Events\ModuleActivatedEvent;
use HSubscription\LaravelSubscription\Events\ModuleDeactivatedEvent;
use HSubscription\LaravelSubscription\Events\PlanChangedEvent;
use HSubscription\LaravelSubscription\Events\SubscriptionCancelledEvent;
use HSubscription\LaravelSubscription\Events\SubscriptionCreatedEvent;
use HSubscription\LaravelSubscription\Events\SubscriptionDeletedEvent;
use HSubscription\LaravelSubscription\Events\SubscriptionExpiredEvent;
use HSubscription\LaravelSubscription\Events\SubscriptionRenewedEvent;
use HSubscription\LaravelSubscription\Events\SubscriptionResumedEvent;
use HSubscription\LaravelSubscription\Events\TrialEndedEvent;
use HSubscription\LaravelSubscription\Events\UsageRecordedEvent;
use HSubscription\LaravelSubscription\Models\Feature;
use HSubscription\LaravelSubscription\Models\Module;
use HSubscription\LaravelSubscription\Models\Plan;
use HSubscription\LaravelSubscription\Services\FeatureService;
use HSubscription\LaravelSubscription\Services\ModuleService;
use HSubscription\LaravelSubscription\Services\SubscriptionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake();

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

it('fires SubscriptionCreated event', function () {
    $plan = Plan::factory()->create();
    $this->subscriptionService->create($this->user, $plan);

    Event::assertDispatched(SubscriptionCreatedEvent::class);
});

it('fires SubscriptionCancelled event', function () {
    $plan = Plan::factory()->create();
    $subscription = $this->subscriptionService->create($this->user, $plan);
    $this->subscriptionService->cancel($subscription);

    Event::assertDispatched(SubscriptionCancelledEvent::class);
});

it('fires SubscriptionResumed event', function () {
    $plan = Plan::factory()->create();
    $subscription = $this->subscriptionService->create($this->user, $plan);
    $this->subscriptionService->cancel($subscription);
    $this->subscriptionService->resume($subscription);

    Event::assertDispatched(SubscriptionResumedEvent::class);
});

it('fires SubscriptionExpired event', function () {
    $plan = Plan::factory()->create();
    $subscription = $this->subscriptionService->create($this->user, $plan);
    $this->subscriptionService->expire($subscription);

    Event::assertDispatched(SubscriptionExpiredEvent::class);
});

it('fires SubscriptionRenewed event', function () {
    $plan = Plan::factory()->create();
    $subscription = $this->subscriptionService->create($this->user, $plan);
    $this->subscriptionService->renew($subscription);

    Event::assertDispatched(SubscriptionRenewedEvent::class);
});

it('fires SubscriptionDeleted event', function () {
    $plan = Plan::factory()->create();
    $subscription = $this->subscriptionService->create($this->user, $plan);
    $this->subscriptionService->forceDelete($subscription);

    Event::assertDispatched(SubscriptionDeletedEvent::class);
});

it('fires PlanChanged event', function () {
    $oldPlan = Plan::factory()->create();
    $newPlan = Plan::factory()->create();
    $subscription = $this->subscriptionService->create($this->user, $oldPlan);
    $this->subscriptionService->changePlan($subscription, $newPlan);

    Event::assertDispatched(PlanChangedEvent::class);
});

it('fires TrialEnded event', function () {
    $plan = Plan::factory()->create(['trial_days' => 7]);
    $subscription = $this->subscriptionService->create($this->user, $plan);
    $this->subscriptionService->convertTrialToPaid($subscription);

    Event::assertDispatched(TrialEndedEvent::class);
});

it('fires UsageRecorded event', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->consumable()->create(['default_value' => 100]);
    $plan->features()->attach($feature->id, ['value' => 100]);

    $this->user->subscribe($plan);
    $this->featureService->consume($this->user, $feature, 10);

    Event::assertDispatched(UsageRecordedEvent::class);
});

it('fires FeatureLimitReached event', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->consumable()->create(['default_value' => 100]);
    $plan->features()->attach($feature->id, ['value' => 100]);

    $this->user->subscribe($plan);
    $this->featureService->consume($this->user, $feature, 100);

    Event::assertDispatched(FeatureLimitReachedEvent::class);
});

it('fires FeatureLimitExceeded event', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->consumable()->create(['default_value' => 100]);
    $plan->features()->attach($feature->id, ['value' => 100]);

    $this->user->subscribe($plan);
    $this->featureService->consume($this->user, $feature, 150);

    Event::assertDispatched(FeatureLimitExceededEvent::class);
});

it('fires ModuleActivated event', function () {
    $plan = Plan::factory()->create();
    $module = Module::factory()->create();
    $plan->modules()->attach($module->id, ['is_enabled' => true]);

    $subscription = $this->user->subscribe($plan);
    $this->moduleService->activate($subscription, $module);

    Event::assertDispatched(ModuleActivatedEvent::class);
});

it('fires ModuleDeactivated event', function () {
    $plan = Plan::factory()->create();
    $module = Module::factory()->create();
    $plan->modules()->attach($module->id, ['is_enabled' => true]);

    $subscription = $this->user->subscribe($plan);
    $subscription->moduleActivations()->create([
        'module_id' => $module->id,
        'is_active' => true,
        'activated_at' => now(),
    ]);

    $this->moduleService->deactivate($subscription, $module);

    Event::assertDispatched(ModuleDeactivatedEvent::class);
});
