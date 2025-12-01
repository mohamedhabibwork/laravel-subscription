<?php

use HSubscription\LaravelSubscription\Concerns\HasSubscriptions;
use HSubscription\LaravelSubscription\Enums\SubscriptionStatus;
use HSubscription\LaravelSubscription\Models\Feature;
use HSubscription\LaravelSubscription\Models\Plan;
use HSubscription\LaravelSubscription\Models\Subscription;
use HSubscription\LaravelSubscription\Services\FeatureService;
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
});

it('generates unique uuids for subscriptions', function () {
    $plan = Plan::factory()->create();
    $subscription1 = $this->user->subscribe($plan);
    $subscription2 = $this->user->subscribe($plan, ['name' => 'secondary']);

    expect($subscription1->uuid)->not->toBe($subscription2->uuid);
});

it('handles soft deletes correctly', function () {
    $plan = Plan::factory()->create();
    $subscription = $this->user->subscribe($plan);

    $subscription->delete();

    expect($subscription->trashed())->toBeTrue()
        ->and(Subscription::withTrashed()->find($subscription->id))->not->toBeNull()
        ->and(Subscription::find($subscription->id))->toBeNull();
});

it('can restore soft deleted subscription', function () {
    $plan = Plan::factory()->create();
    $subscription = $this->user->subscribe($plan);

    $subscription->delete();
    $subscription->restore();

    expect($subscription->trashed())->toBeFalse()
        ->and(Subscription::find($subscription->id))->not->toBeNull();
});

it('handles subscription status transitions', function () {
    $plan = Plan::factory()->create();
    $subscription = $this->subscriptionService->create($this->user, $plan);

    // Active -> Paused
    $subscription = $this->subscriptionService->pause($subscription);
    expect($subscription->status)->toBe(SubscriptionStatus::Paused);

    // Paused -> Active
    $subscription = $this->subscriptionService->unpause($subscription);
    expect($subscription->status)->toBe(SubscriptionStatus::Active);

    // Active -> Cancelled
    $subscription = $this->subscriptionService->cancel($subscription);
    expect($subscription->status)->toBe(SubscriptionStatus::Cancelled);

    // Cancelled -> Active
    $subscription = $this->subscriptionService->resume($subscription);
    expect($subscription->status)->toBe(SubscriptionStatus::Active);
});

it('handles feature access when subscription expired', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->boolean()->create();
    $plan->features()->attach($feature->id, ['value' => 1]);

    $subscription = $this->user->subscribe($plan);
    $subscription->update(['status' => SubscriptionStatus::Expired, 'ends_at' => now()->subDay()]);

    expect($this->user->hasFeature($feature))->toBeFalse()
        ->and($this->featureService->hasAccess($this->user, $feature))->toBeFalse();
});

it('handles usage tracking with expired valid_until dates', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->consumable()->create(['default_value' => 100]);
    $plan->features()->attach($feature->id, ['value' => 100]);

    $subscription = $this->user->subscribe($plan);
    $subscription->usage()->create([
        'feature_id' => $feature->id,
        'used' => 50,
        'limit' => 100,
        'valid_until' => now()->subDay(),
    ]);

    // Should create new usage record when consuming
    $this->featureService->consume($this->user, $feature, 10);

    $validUsage = $subscription->usage()->valid()->where('feature_id', $feature->id)->first();
    expect($validUsage)->not->toBeNull()
        ->and($validUsage->used)->toBe(10);
});

it('handles plan changes with no previous plan', function () {
    $plan = Plan::factory()->create();
    $newPlan = Plan::factory()->create();

    $subscription = $this->subscriptionService->create($this->user, $plan);

    // This should work fine as subscription already has a plan
    $change = $this->subscriptionService->changePlan($subscription, $newPlan);

    expect($change->from_plan_id)->toBe($plan->id)
        ->and($change->to_plan_id)->toBe($newPlan->id);
});

it('handles custom limits exceeding plan limits', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->limit()->create(['default_value' => 100]);
    $plan->features()->attach($feature->id, ['value' => 100]);

    $subscription = $this->user->subscribe($plan);

    // Set custom limit higher than plan limit
    $this->featureService->setCustomLimit($subscription, $feature, 500);

    expect($this->featureService->getFeatureLimit($subscription, $feature))->toBe(500);
});

it('handles polymorphic relationship queries', function () {
    $user = new class extends Model
    {
        use HasSubscriptions;

        protected $table = 'users';

        public $timestamps = false;
    };
    $user->id = 1;

    $team = new class extends Model
    {
        use HasSubscriptions;

        protected $table = 'teams';

        public $timestamps = false;
    };
    $team->id = 1;

    $plan = Plan::factory()->create();

    $userSubscription = $user->subscribe($plan);
    $teamSubscription = $team->subscribe($plan);

    expect(Subscription::where('subscribable_type', get_class($user))->count())->toBe(1)
        ->and(Subscription::where('subscribable_type', get_class($team))->count())->toBe(1);
});
