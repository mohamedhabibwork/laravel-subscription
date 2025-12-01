<?php

use HSubscription\LaravelSubscription\Models\Feature;
use HSubscription\LaravelSubscription\Models\Module;
use HSubscription\LaravelSubscription\Models\Plan;

it('generates uuid on creation', function () {
    $module = Module::factory()->create();

    expect($module->uuid)->not->toBeNull();
});

it('can have a parent module', function () {
    $parent = Module::factory()->create();
    $child = Module::factory()->childOf($parent)->create();

    expect($child->parent_id)->toBe($parent->id)
        ->and($child->parent)->toBeInstanceOf(Module::class);
});

it('can have children modules', function () {
    $parent = Module::factory()->create();
    Module::factory()->count(3)->childOf($parent)->create();

    expect($parent->children)->toHaveCount(3);
});

it('can have many features', function () {
    $module = Module::factory()->create();
    Feature::factory()->count(3)->forModule($module)->create();

    expect($module->features)->toHaveCount(3);
});

it('belongs to many plans', function () {
    $module = Module::factory()->create();
    $plans = Plan::factory()->count(2)->create();

    $module->plans()->attach($plans->pluck('id')->toArray(), ['is_enabled' => true]);

    expect($module->plans)->toHaveCount(2);
});

it('can scope active modules', function () {
    Module::factory()->active()->create();
    Module::factory()->inactive()->create();

    expect(Module::active()->count())->toBe(1);
});

it('can scope root modules', function () {
    $root = Module::factory()->create();
    Module::factory()->childOf($root)->create();

    expect(Module::root()->count())->toBe(1);
});
