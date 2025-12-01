<?php

namespace HSubscription\LaravelSubscription\Database\Factories;

use HSubscription\LaravelSubscription\Models\Plan;
use HSubscription\LaravelSubscription\Models\Subscription;
use HSubscription\LaravelSubscription\Models\SubscriptionChange;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\HSubscription\LaravelSubscription\Models\SubscriptionChange>
 */
class SubscriptionChangeFactory extends Factory
{
    protected $model = SubscriptionChange::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'subscription_id' => Subscription::factory(),
            'from_plan_id' => Plan::factory(),
            'to_plan_id' => Plan::factory(),
            'change_type' => $this->faker->randomElement(['upgrade', 'downgrade', 'switch']),
            'is_immediate' => true,
            'scheduled_for' => null,
            'applied_at' => now(),
            'proration_amount' => $this->faker->randomFloat(2, 0, 100),
            'metadata' => null,
        ];
    }

    public function upgrade(): static
    {
        return $this->state(fn (array $attributes) => [
            'change_type' => 'upgrade',
        ]);
    }

    public function downgrade(): static
    {
        return $this->state(fn (array $attributes) => [
            'change_type' => 'downgrade',
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_immediate' => false,
            'scheduled_for' => now()->addWeek(),
            'applied_at' => null,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'applied_at' => null,
        ]);
    }
}
