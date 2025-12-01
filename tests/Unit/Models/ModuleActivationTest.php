<?php

use HSubscription\LaravelSubscription\Models\Module;
use HSubscription\LaravelSubscription\Models\ModuleActivation;
use HSubscription\LaravelSubscription\Models\Subscription;

it('generates uuid on creation', function () {
    $activation = ModuleActivation::factory()->create();

    expect($activation->uuid)->not->toBeNull();
});

it('belongs to a subscription', function () {
    $subscription = Subscription::factory()->create();
    $activation = ModuleActivation::factory()->create(['subscription_id' => $subscription->id]);

    expect($activation->subscription)->toBeInstanceOf(Subscription::class)
        ->and($activation->subscription_id)->toBe($subscription->id);
});

it('belongs to a module', function () {
    $module = Module::factory()->create();
    $activation = ModuleActivation::factory()->create(['module_id' => $module->id]);

    expect($activation->module)->toBeInstanceOf(Module::class)
        ->and($activation->module_id)->toBe($module->id);
});

it('can be active or inactive', function () {
    $active = ModuleActivation::factory()->create(['is_active' => true]);
    $inactive = ModuleActivation::factory()->inactive()->create();

    expect($active->is_active)->toBeTrue()
        ->and($inactive->is_active)->toBeFalse();
});

it('can scope active activations', function () {
    ModuleActivation::factory()->create(['is_active' => true]);
    ModuleActivation::factory()->inactive()->create();

    expect(ModuleActivation::active()->count())->toBe(1);
});

it('tracks activation and deactivation timestamps', function () {
    $activation = ModuleActivation::factory()->create([
        'activated_at' => now()->subDay(),
        'deactivated_at' => null,
    ]);

    expect($activation->activated_at)->not->toBeNull()
        ->and($activation->deactivated_at)->toBeNull();

    $activation->update([
        'is_active' => false,
        'deactivated_at' => now(),
    ]);

    expect($activation->deactivated_at)->not->toBeNull();
});
