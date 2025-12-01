<?php

use HSubscription\LaravelSubscription\Concerns\HasSubscriptions;
use HSubscription\LaravelSubscription\Events\ModuleActivated;
use HSubscription\LaravelSubscription\Events\ModuleDeactivated;
use HSubscription\LaravelSubscription\Models\Feature;
use HSubscription\LaravelSubscription\Models\Module;
use HSubscription\LaravelSubscription\Models\Plan;
use HSubscription\LaravelSubscription\Services\ModuleService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake();

    $this->user = new class extends Model
    {
        use HasSubscriptions;

        protected $table = 'users';

        public $timestamps = false;
    };
    $this->user->id = 1;

    $this->service = app(ModuleService::class);
});

it('can check module access', function () {
    $plan = Plan::factory()->create();
    $module = Module::factory()->create();
    $plan->modules()->attach($module->id, ['is_enabled' => true]);

    $subscription = $this->user->subscribe($plan);
    $subscription->moduleActivations()->create([
        'module_id' => $module->id,
        'is_active' => true,
        'activated_at' => now(),
    ]);

    expect($this->service->hasAccess($this->user, $module))->toBeTrue();
});

it('returns false for module access when not in plan', function () {
    $plan = Plan::factory()->create();
    $module = Module::factory()->create();

    $subscription = $this->user->subscribe($plan);

    expect($this->service->hasAccess($this->user, $module))->toBeFalse();
});

it('can activate module', function () {
    $plan = Plan::factory()->create();
    $module = Module::factory()->create();
    $plan->modules()->attach($module->id, ['is_enabled' => true]);

    $subscription = $this->user->subscribe($plan);

    $activation = $this->service->activate($subscription, $module);

    expect($activation->is_active)->toBeTrue()
        ->and($activation->module_id)->toBe($module->id);

    Event::assertDispatched(ModuleActivated::class);
});

it('throws exception when activating module not in plan', function () {
    $plan = Plan::factory()->create();
    $module = Module::factory()->create();

    $subscription = $this->user->subscribe($plan);

    expect(fn () => $this->service->activate($subscription, $module))
        ->toThrow(\Exception::class);
});

it('can deactivate module', function () {
    $plan = Plan::factory()->create();
    $module = Module::factory()->create();
    $plan->modules()->attach($module->id, ['is_enabled' => true]);

    $subscription = $this->user->subscribe($plan);
    $subscription->moduleActivations()->create([
        'module_id' => $module->id,
        'is_active' => true,
        'activated_at' => now(),
    ]);

    $activation = $this->service->deactivate($subscription, $module);

    expect($activation->is_active)->toBeFalse()
        ->and($activation->deactivated_at)->not->toBeNull();

    Event::assertDispatched(ModuleDeactivated::class);
});

it('throws exception when deactivating non-activated module', function () {
    $plan = Plan::factory()->create();
    $module = Module::factory()->create();
    $plan->modules()->attach($module->id, ['is_enabled' => true]);

    $subscription = $this->user->subscribe($plan);

    expect(fn () => $this->service->deactivate($subscription, $module))
        ->toThrow(\Exception::class);
});

it('can get active modules', function () {
    $plan = Plan::factory()->create();
    $module1 = Module::factory()->create();
    $module2 = Module::factory()->create();
    $plan->modules()->attach([$module1->id, $module2->id], ['is_enabled' => true]);

    $subscription = $this->user->subscribe($plan);
    $subscription->moduleActivations()->create([
        'module_id' => $module1->id,
        'is_active' => true,
        'activated_at' => now(),
    ]);
    $subscription->moduleActivations()->create([
        'module_id' => $module2->id,
        'is_active' => false,
        'activated_at' => now(),
    ]);

    $activeModules = $this->service->getActiveModules($subscription);

    expect($activeModules)->toHaveCount(1)
        ->and($activeModules->first()->id)->toBe($module1->id);
});

it('can get module features', function () {
    $plan = Plan::factory()->create();
    $module = Module::factory()->create();
    $feature = Feature::factory()->create(['module_id' => $module->id]);
    $plan->modules()->attach($module->id, ['is_enabled' => true]);

    $subscription = $this->user->subscribe($plan);
    $subscription->moduleActivations()->create([
        'module_id' => $module->id,
        'is_active' => true,
        'activated_at' => now(),
    ]);

    $features = $this->service->getModuleFeatures($subscription, $module);

    expect($features)->toHaveCount(1)
        ->and($features->first()->id)->toBe($feature->id);
});

it('can activate all plan modules', function () {
    $plan = Plan::factory()->create();
    $module1 = Module::factory()->create();
    $module2 = Module::factory()->create();
    $plan->modules()->attach([$module1->id, $module2->id], ['is_enabled' => true]);

    $subscription = $this->user->subscribe($plan);

    $this->service->activatePlanModules($subscription);

    expect($subscription->moduleActivations()->where('is_active', true)->count())->toBe(2);
});
