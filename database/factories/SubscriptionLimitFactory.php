<?php

namespace HSubscription\LaravelSubscription\Database\Factories;

use HSubscription\LaravelSubscription\Models\Feature;
use HSubscription\LaravelSubscription\Models\Subscription;
use HSubscription\LaravelSubscription\Models\SubscriptionLimit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\HSubscription\LaravelSubscription\Models\SubscriptionLimit>
 */
class SubscriptionLimitFactory extends Factory
{
    protected $model = SubscriptionLimit::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'subscription_id' => Subscription::factory(),
            'feature_id' => Feature::factory(),
            'custom_limit' => $this->faker->numberBetween(10, 1000),
            'limit_type' => $this->faker->randomElement(['hard', 'soft']),
            'warning_threshold' => $this->faker->numberBetween(50, 90),
            'metadata' => null,
        ];
    }

    public function hard(): static
    {
        return $this->state(fn (array $attributes) => [
            'limit_type' => 'hard',
        ]);
    }

    public function soft(): static
    {
        return $this->state(fn (array $attributes) => [
            'limit_type' => 'soft',
        ]);
    }
}
