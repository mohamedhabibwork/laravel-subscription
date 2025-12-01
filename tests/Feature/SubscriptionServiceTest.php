<?php

use HSubscription\LaravelSubscription\Concerns\HasSubscriptions;
use HSubscription\LaravelSubscription\Enums\PlanChangeType;
use HSubscription\LaravelSubscription\Enums\SubscriptionStatus;
use HSubscription\LaravelSubscription\Events\PlanChangedEvent;
use HSubscription\LaravelSubscription\Events\SubscriptionCancelledEvent;
use HSubscription\LaravelSubscription\Events\SubscriptionCreatedEvent;
use HSubscription\LaravelSubscription\Models\Plan;
use HSubscription\LaravelSubscription\Models\Subscription;
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

    $this->service = app(SubscriptionService::class);
});

it('creates subscription and fires event', function () {
    $plan = Plan::factory()->create();

    $subscription = $this->service->create($this->user, $plan);

    expect($subscription)->toBeInstanceOf(Subscription::class)
        ->and($subscription->plan_id)->toBe($plan->id);

    Event::assertDispatched(SubscriptionCreatedEvent::class);
});

it('can cancel subscription', function () {
    $plan = Plan::factory()->create();
    $subscription = $this->service->create($this->user, $plan);

    $cancelled = $this->service->cancel($subscription, false);

    expect($cancelled->status)->toBe(SubscriptionStatus::Cancelled)
        ->and($cancelled->cancelled_at)->not->toBeNull();

    Event::assertDispatched(SubscriptionCancelledEvent::class);
});

it('can change plan', function () {
    $oldPlan = Plan::factory()->create(['price' => 10.00]);
    $newPlan = Plan::factory()->create(['price' => 20.00]);
    $subscription = $this->service->create($this->user, $oldPlan);

    $change = $this->service->changePlan($subscription, $newPlan);

    expect($change->to_plan_id)->toBe($newPlan->id)
        ->and($change->change_type)->toBe(PlanChangeType::Upgrade)
        ->and($subscription->fresh()->plan_id)->toBe($newPlan->id);

    Event::assertDispatched(PlanChangedEvent::class);
});

it('can resume cancelled subscription', function () {
    $plan = Plan::factory()->create();
    $subscription = $this->service->create($this->user, $plan);
    $this->service->cancel($subscription);

    $resumed = $this->service->resume($subscription);

    expect($resumed->status)->toBe(SubscriptionStatus::Active)
        ->and($resumed->cancelled_at)->toBeNull();
});

it('creates subscription with trial period', function () {
    $plan = Plan::factory()->create(['trial_days' => 14]);

    $subscription = $this->service->create($this->user, $plan);

    expect($subscription->status)->toBe(SubscriptionStatus::OnTrial)
        ->and($subscription->trial_ends_at)->not->toBeNull();
});

it('can convert trial to paid', function () {
    $plan = Plan::factory()->monthly()->create(['trial_days' => 7]);
    $subscription = $this->service->create($this->user, $plan);

    $converted = $this->service->convertTrialToPaid($subscription);

    expect($converted->status)->toBe(SubscriptionStatus::Active)
        ->and($converted->trial_ends_at)->toBeNull()
        ->and($converted->ends_at)->not->toBeNull();
});

it('can pause subscription', function () {
    $plan = Plan::factory()->create();
    $subscription = $this->service->create($this->user, $plan);

    $paused = $this->service->pause($subscription);

    expect($paused->status)->toBe(SubscriptionStatus::Paused)
        ->and($paused->paused_at)->not->toBeNull();
});

it('can unpause subscription', function () {
    $plan = Plan::factory()->create();
    $subscription = $this->service->create($this->user, $plan);
    $this->service->pause($subscription);

    $unpaused = $this->service->unpause($subscription);

    expect($unpaused->status)->toBe(SubscriptionStatus::Active)
        ->and($unpaused->paused_at)->toBeNull();
});

it('can renew subscription', function () {
    $plan = Plan::factory()->monthly()->create();
    $subscription = $this->service->create($this->user, $plan);
    $originalEndsAt = $subscription->ends_at;

    $renewed = $this->service->renew($subscription);

    expect($renewed->status)->toBe(SubscriptionStatus::Active)
        ->and($renewed->ends_at)->not->toBe($originalEndsAt);
});

it('can expire subscription', function () {
    $plan = Plan::factory()->create();
    $subscription = $this->service->create($this->user, $plan);

    $expired = $this->service->expire($subscription);

    expect($expired->status)->toBe(SubscriptionStatus::Expired);
});

it('can cancel subscription immediately', function () {
    $plan = Plan::factory()->create();
    $subscription = $this->service->create($this->user, $plan);

    $cancelled = $this->service->cancel($subscription, true);

    expect($cancelled->status)->toBe(SubscriptionStatus::Cancelled)
        ->and($cancelled->ends_at)->not->toBeNull();
});

it('can schedule plan change', function () {
    $oldPlan = Plan::factory()->create();
    $newPlan = Plan::factory()->create();
    $subscription = $this->service->create($this->user, $oldPlan);
    $scheduledFor = now()->addWeek();

    $change = $this->service->changePlan($subscription, $newPlan, false, $scheduledFor);

    expect($change->is_immediate)->toBeFalse()
        ->and($change->scheduled_for)->toBe($scheduledFor)
        ->and($subscription->fresh()->plan_id)->toBe($oldPlan->id);
});

it('calculates proration for plan change', function () {
    $oldPlan = Plan::factory()->create(['price' => 10.00]);
    $newPlan = Plan::factory()->create(['price' => 20.00]);
    $subscription = $this->service->create($this->user, $oldPlan, [
        'starts_at' => now()->subDays(15),
        'ends_at' => now()->addDays(15),
    ]);

    $change = $this->service->changePlan($subscription, $newPlan);

    expect($change->proration_amount)->not->toBeNull();
});

it('throws exception when resuming non-cancelled subscription', function () {
    $plan = Plan::factory()->create();
    $subscription = $this->service->create($this->user, $plan);

    expect(fn () => $this->service->resume($subscription))
        ->toThrow(\Exception::class, 'Subscription is not cancelled');
});

it('throws exception when pausing non-active subscription', function () {
    $plan = Plan::factory()->create();
    $subscription = $this->service->create($this->user, $plan);
    $this->service->cancel($subscription);

    expect(fn () => $this->service->pause($subscription))
        ->toThrow(\Exception::class);
});

it('throws exception when unpausing non-paused subscription', function () {
    $plan = Plan::factory()->create();
    $subscription = $this->service->create($this->user, $plan);

    expect(fn () => $this->service->unpause($subscription))
        ->toThrow(\Exception::class, 'Subscription is not paused');
});

it('throws exception when converting non-trial subscription', function () {
    $plan = Plan::factory()->create(['trial_days' => 0]);
    $subscription = $this->service->create($this->user, $plan);

    expect(fn () => $this->service->convertTrialToPaid($subscription))
        ->toThrow(\Exception::class, 'Subscription is not on trial');
});
