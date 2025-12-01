<?php

namespace HSubscription\LaravelSubscription\Database\Factories;

use HSubscription\LaravelSubscription\Models\Module;
use HSubscription\LaravelSubscription\Models\ModuleActivation;
use HSubscription\LaravelSubscription\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\HSubscription\LaravelSubscription\Models\ModuleActivation>
 */
class ModuleActivationFactory extends Factory
{
    protected $model = ModuleActivation::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'subscription_id' => Subscription::factory(),
            'module_id' => Module::factory(),
            'is_active' => true,
            'activated_at' => now(),
            'deactivated_at' => null,
            'metadata' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'deactivated_at' => now(),
        ]);
    }
}
