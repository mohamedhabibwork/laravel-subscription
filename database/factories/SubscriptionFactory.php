<?php

namespace HSubscription\LaravelSubscription\Database\Factories;

use HSubscription\LaravelSubscription\Enums\SubscriptionStatus;
use HSubscription\LaravelSubscription\Models\Plan;
use HSubscription\LaravelSubscription\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\HSubscription\LaravelSubscription\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'subscribable_type' => 'App\Models\User',
            'subscribable_id' => 1,
            'plan_id' => Plan::factory(),
            'name' => 'default',
            'status' => SubscriptionStatus::Active,
            'trial_ends_at' => null,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'cancelled_at' => null,
            'paused_at' => null,
            'resumed_at' => null,
            'metadata' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::Active,
        ]);
    }

    public function onTrial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::OnTrial,
            'trial_ends_at' => now()->addDays(7),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::Expired,
            'ends_at' => now()->subDay(),
        ]);
    }

    public function pastDue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::PastDue,
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::Paused,
            'paused_at' => now(),
        ]);
    }
}
