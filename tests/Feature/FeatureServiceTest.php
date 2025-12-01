<?php

use HSubscription\LaravelSubscription\Concerns\HasSubscriptions;
use HSubscription\LaravelSubscription\Enums\LimitType;
use HSubscription\LaravelSubscription\Enums\SubscriptionStatus;
use HSubscription\LaravelSubscription\Events\FeatureLimitExceededEvent;
use HSubscription\LaravelSubscription\Events\UsageRecordedEvent;
use HSubscription\LaravelSubscription\Models\Feature;
use HSubscription\LaravelSubscription\Models\Plan;
use HSubscription\LaravelSubscription\Services\FeatureService;
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

    $this->service = app(FeatureService::class);
});

it('can check feature access', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->boolean()->create();
    $plan->features()->attach($feature->id, ['value' => 1]);

    $subscription = $this->user->subscribe($plan);

    expect($this->service->hasAccess($this->user, $feature))->toBeTrue();
});

it('can consume feature', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->consumable()->create(['default_value' => 100]);
    $plan->features()->attach($feature->id, ['value' => 100]);

    $subscription = $this->user->subscribe($plan);

    $result = $this->service->consume($this->user, $feature, 10);

    expect($result)->toBeTrue();
    Event::assertDispatched(UsageRecordedEvent::class);
});

it('prevents consuming beyond limit', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->consumable()->create(['default_value' => 100]);
    $plan->features()->attach($feature->id, ['value' => 100]);

    $subscription = $this->user->subscribe($plan);

    $result = $this->service->consume($this->user, $feature, 150);

    expect($result)->toBeFalse();
    Event::assertDispatched(FeatureLimitExceededEvent::class);
});

it('returns false for feature access when subscription is not active', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->boolean()->create();
    $plan->features()->attach($feature->id, ['value' => 1]);

    $subscription = $this->user->subscribe($plan);
    $subscription->update(['status' => SubscriptionStatus::Cancelled]);

    expect($this->service->hasAccess($this->user, $feature))->toBeFalse();
});

it('can get remaining usage', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->consumable()->create(['default_value' => 100]);
    $plan->features()->attach($feature->id, ['value' => 100]);

    $subscription = $this->user->subscribe($plan);
    $this->service->consume($this->user, $feature, 30);

    expect($this->service->getRemainingUsage($subscription, $feature))->toBe(70);
});

it('can get feature limit from plan override', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->limit()->create(['default_value' => 50]);
    $plan->features()->attach($feature->id, ['value' => 200]);

    $subscription = $this->user->subscribe($plan);

    expect($this->service->getFeatureLimit($subscription, $feature))->toBe(200);
});

it('can set custom limit', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->limit()->create(['default_value' => 100]);
    $plan->features()->attach($feature->id, ['value' => 100]);

    $subscription = $this->user->subscribe($plan);

    $limit = $this->service->setCustomLimit($subscription, $feature, 500, LimitType::Hard, 400);

    expect($limit->custom_limit)->toBe(500)
        ->and($limit->limit_type)->toBe(LimitType::Hard)
        ->and($limit->warning_threshold)->toBe(400);
});

it('can reset usage for specific feature', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->consumable()->create(['default_value' => 100]);
    $plan->features()->attach($feature->id, ['value' => 100]);

    $subscription = $this->user->subscribe($plan);
    $this->service->consume($this->user, $feature, 50);

    $this->service->resetUsage($subscription, $feature);

    expect($this->service->getRemainingUsage($subscription, $feature))->toBe(100);
});

it('can reset all usage', function () {
    $plan = Plan::factory()->create();
    $feature1 = Feature::factory()->consumable()->create(['default_value' => 100]);
    $feature2 = Feature::factory()->consumable()->create(['default_value' => 200]);
    $plan->features()->attach([$feature1->id, $feature2->id], ['value' => 100]);

    $subscription = $this->user->subscribe($plan);
    $this->service->consume($this->user, $feature1, 50);
    $this->service->consume($this->user, $feature2, 100);

    $this->service->resetUsage($subscription);

    expect($this->service->getRemainingUsage($subscription, $feature1))->toBe(100)
        ->and($this->service->getRemainingUsage($subscription, $feature2))->toBe(200);
});
