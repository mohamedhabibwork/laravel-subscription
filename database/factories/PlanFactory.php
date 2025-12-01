<?php

namespace HSubscription\LaravelSubscription\Database\Factories;

use HSubscription\LaravelSubscription\Enums\BillingInterval;
use HSubscription\LaravelSubscription\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\HSubscription\LaravelSubscription\Models\Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            'uuid' => (string) Str::uuid(),
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 9.99, 999.99),
            'currency' => 'USD',
            'interval' => $this->faker->randomElement([BillingInterval::Monthly, BillingInterval::Yearly, BillingInterval::Weekly]),
            'interval_count' => 1,
            'trial_days' => $this->faker->numberBetween(0, 30),
            'grace_days' => $this->faker->numberBetween(0, 7),
            'is_active' => true,
            'tier' => $this->faker->randomElement(['personal', 'business', 'enterprise', null]),
            'metadata' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'interval' => BillingInterval::Monthly,
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'interval' => BillingInterval::Yearly,
        ]);
    }
}
