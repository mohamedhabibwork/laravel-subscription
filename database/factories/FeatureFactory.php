<?php

namespace HSubscription\LaravelSubscription\Database\Factories;

use HSubscription\LaravelSubscription\Models\Feature;
use HSubscription\LaravelSubscription\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\HSubscription\LaravelSubscription\Models\Feature>
 */
class FeatureFactory extends Factory
{
    protected $model = Feature::class;

    public function definition(): array
    {
        $name = $this->faker->word();

        return [
            'uuid' => (string) Str::uuid(),
            'module_id' => null,
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['boolean', 'limit', 'consumable']),
            'default_value' => $this->faker->numberBetween(0, 1000),
            'reset_period' => $this->faker->randomElement(['never', 'monthly', 'yearly', 'daily']),
            'is_active' => true,
            'metadata' => null,
        ];
    }

    public function boolean(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'boolean',
            'default_value' => 1,
        ]);
    }

    public function limit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'limit',
            'default_value' => $this->faker->numberBetween(10, 1000),
        ]);
    }

    public function consumable(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'consumable',
            'default_value' => $this->faker->numberBetween(100, 10000),
        ]);
    }

    public function forModule(Module $module): static
    {
        return $this->state(fn (array $attributes) => [
            'module_id' => $module->id,
        ]);
    }
}
