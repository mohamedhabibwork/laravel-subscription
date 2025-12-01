<?php

namespace HSubscription\LaravelSubscription\Database\Factories;

use HSubscription\LaravelSubscription\Models\Feature;
use HSubscription\LaravelSubscription\Models\Subscription;
use HSubscription\LaravelSubscription\Models\SubscriptionUsage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\HSubscription\LaravelSubscription\Models\SubscriptionUsage>
 */
class SubscriptionUsageFactory extends Factory
{
    protected $model = SubscriptionUsage::class;

    public function definition(): array
    {
        $limit = $this->faker->numberBetween(100, 1000);
        $used = $this->faker->numberBetween(0, $limit);

        return [
            'uuid' => (string) Str::uuid(),
            'subscription_id' => Subscription::factory(),
            'feature_id' => Feature::factory(),
            'used' => $used,
            'limit' => $limit,
            'valid_until' => now()->addMonth(),
            'reset_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_until' => now()->subDay(),
        ]);
    }

    public function atLimit(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'used' => $attributes['limit'],
            ];
        });
    }
}
