<?php

use HSubscription\LaravelSubscription\Models\Feature;
use HSubscription\LaravelSubscription\Models\Module;
use HSubscription\LaravelSubscription\Models\Plan;

it('generates uuid on creation', function () {
    $feature = Feature::factory()->create();

    expect($feature->uuid)->not->toBeNull();
});

it('can belong to a module', function () {
    $module = Module::factory()->create();
    $feature = Feature::factory()->forModule($module)->create();

    expect($feature->module_id)->toBe($module->id)
        ->and($feature->module)->toBeInstanceOf(Module::class);
});

it('can be boolean type', function () {
    $feature = Feature::factory()->boolean()->create();

    expect($feature->isBoolean())->toBeTrue()
        ->and($feature->isLimit())->toBeFalse()
        ->and($feature->isConsumable())->toBeFalse();
});

it('can be limit type', function () {
    $feature = Feature::factory()->limit()->create();

    expect($feature->isLimit())->toBeTrue()
        ->and($feature->isBoolean())->toBeFalse();
});

it('can be consumable type', function () {
    $feature = Feature::factory()->consumable()->create();

    expect($feature->isConsumable())->toBeTrue()
        ->and($feature->isBoolean())->toBeFalse();
});

it('belongs to many plans', function () {
    $feature = Feature::factory()->create();
    $plans = Plan::factory()->count(2)->create();

    $feature->plans()->attach($plans->pluck('id')->toArray(), ['value' => 50]);

    expect($feature->plans)->toHaveCount(2);
});
