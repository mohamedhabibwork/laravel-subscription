<?php

use HSubscription\LaravelSubscription\Concerns\HasSubscriptions;
use HSubscription\LaravelSubscription\Enums\SubscriptionStatus;
use HSubscription\LaravelSubscription\Http\Middleware\EnsureHasFeature;
use HSubscription\LaravelSubscription\Http\Middleware\EnsureHasModule;
use HSubscription\LaravelSubscription\Models\Feature;
use HSubscription\LaravelSubscription\Models\Module;
use HSubscription\LaravelSubscription\Models\Plan;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->user = new class extends Model implements Authenticatable
    {
        use HasSubscriptions;
        use \Illuminate\Auth\Authenticatable;

        protected $table = 'users';

        public $timestamps = false;
    };
    $this->user->id = 1;
});

it('allows access when user has feature', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->boolean()->create();
    $plan->features()->attach($feature->id, ['value' => 1]);

    $this->user->subscribe($plan);

    Route::middleware([EnsureHasFeature::class.':'.$feature->slug])->get('/test', fn () => 'ok');

    $response = $this->actingAs($this->user)->get('/test');

    expect($response->status())->toBe(200);
});

it('denies access when user lacks feature', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->boolean()->create();

    $this->user->subscribe($plan);

    Route::middleware([EnsureHasFeature::class.':'.$feature->slug])->get('/test', fn () => 'ok');

    $response = $this->actingAs($this->user)->get('/test');

    expect($response->status())->toBe(403);
});

it('allows access when user has module', function () {
    $plan = Plan::factory()->create();
    $module = Module::factory()->create();
    $plan->modules()->attach($module->id, ['is_enabled' => true]);

    $subscription = $this->user->subscribe($plan);
    $subscription->moduleActivations()->create([
        'module_id' => $module->id,
        'is_active' => true,
        'activated_at' => now(),
    ]);

    Route::middleware([EnsureHasModule::class.':'.$module->slug])->get('/test', fn () => 'ok');

    $response = $this->actingAs($this->user)->get('/test');

    expect($response->status())->toBe(200);
});

it('denies access for unauthenticated user', function () {
    $feature = Feature::factory()->boolean()->create();

    Route::middleware([EnsureHasFeature::class.':'.$feature->slug])->get('/test', fn () => 'ok');

    $response = $this->get('/test');

    expect($response->status())->toBe(401);
});

it('denies access when user model lacks HasSubscriptions trait', function () {
    $userWithoutTrait = new class extends Model implements Authenticatable
    {
        use \Illuminate\Auth\Authenticatable;

        protected $table = 'users';

        public $timestamps = false;
    };
    $userWithoutTrait->id = 2;

    $feature = Feature::factory()->boolean()->create();

    Route::middleware([EnsureHasFeature::class.':'.$feature->slug])->get('/test', fn () => 'ok');

    $response = $this->actingAs($userWithoutTrait)->get('/test');

    expect($response->status())->toBe(500);
});

it('works with multiple subscriptions using subscription name', function () {
    $plan1 = Plan::factory()->create();
    $plan2 = Plan::factory()->create();
    $feature1 = Feature::factory()->boolean()->create();
    $feature2 = Feature::factory()->boolean()->create();

    $plan1->features()->attach($feature1->id, ['value' => 1]);
    $plan2->features()->attach($feature2->id, ['value' => 1]);

    $this->user->subscribe($plan1, ['name' => 'main']);
    $this->user->subscribe($plan2, ['name' => 'secondary']);

    Route::middleware([EnsureHasFeature::class.':'.$feature1->slug.',default'])->get('/test1', fn () => 'ok');
    Route::middleware([EnsureHasFeature::class.':'.$feature2->slug.',secondary'])->get('/test2', fn () => 'ok');

    $response1 = $this->actingAs($this->user)->get('/test1');
    $response2 = $this->actingAs($this->user)->get('/test2');

    expect($response1->status())->toBe(200)
        ->and($response2->status())->toBe(200);
});

it('denies access when subscription is cancelled', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->boolean()->create();
    $plan->features()->attach($feature->id, ['value' => 1]);

    $subscription = $this->user->subscribe($plan);
    $subscription->update(['status' => SubscriptionStatus::Cancelled]);

    Route::middleware([EnsureHasFeature::class.':'.$feature->slug])->get('/test', fn () => 'ok');

    $response = $this->actingAs($this->user)->get('/test');

    expect($response->status())->toBe(403);
});

it('denies access when subscription is expired', function () {
    $plan = Plan::factory()->create();
    $feature = Feature::factory()->boolean()->create();
    $plan->features()->attach($feature->id, ['value' => 1]);

    $subscription = $this->user->subscribe($plan);
    $subscription->update(['status' => SubscriptionStatus::Expired, 'ends_at' => now()->subDay()]);

    Route::middleware([EnsureHasFeature::class.':'.$feature->slug])->get('/test', fn () => 'ok');

    $response = $this->actingAs($this->user)->get('/test');

    expect($response->status())->toBe(403);
});

it('allows access when subscription is on trial', function () {
    $plan = Plan::factory()->create(['trial_days' => 7]);
    $feature = Feature::factory()->boolean()->create();
    $plan->features()->attach($feature->id, ['value' => 1]);

    $this->user->subscribe($plan);

    Route::middleware([EnsureHasFeature::class.':'.$feature->slug])->get('/test', fn () => 'ok');

    $response = $this->actingAs($this->user)->get('/test');

    expect($response->status())->toBe(200);
});

it('denies module access for unauthenticated user', function () {
    $module = Module::factory()->create();

    Route::middleware([EnsureHasModule::class.':'.$module->slug])->get('/test', fn () => 'ok');

    $response = $this->get('/test');

    expect($response->status())->toBe(401);
});

it('denies module access when subscription is cancelled', function () {
    $plan = Plan::factory()->create();
    $module = Module::factory()->create();
    $plan->modules()->attach($module->id, ['is_enabled' => true]);

    $subscription = $this->user->subscribe($plan);
    $subscription->moduleActivations()->create([
        'module_id' => $module->id,
        'is_active' => true,
        'activated_at' => now(),
    ]);
    $subscription->update(['status' => SubscriptionStatus::Cancelled]);

    Route::middleware([EnsureHasModule::class.':'.$module->slug])->get('/test', fn () => 'ok');

    $response = $this->actingAs($this->user)->get('/test');

    expect($response->status())->toBe(403);
});
