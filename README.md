# Laravel Subscription

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mohamedhabibwork/laravel-subscription.svg?style=flat-square)](https://packagist.org/packages/mohamedhabibwork/laravel-subscription)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/mohamedhabibwork/laravel-subscription/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mohamedhabibwork/laravel-subscription/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/mohamedhabibwork/laravel-subscription/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/mohamedhabibwork/laravel-subscription/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/mohamedhabibwork/laravel-subscription.svg?style=flat-square)](https://packagist.org/packages/mohamedhabibwork/laravel-subscription)

A flexible Laravel package that enables comprehensive subscription management for any subscribable entity with support for multiple plans, features, modules, metering, usage limits, and polymorphic relationships.

## Features

- **Polymorphic Subscriber System**: Any model can become a subscriber through morphable relationships
- **Flexible Plans**: Create subscription plans with pricing, billing intervals, trial periods, and grace periods
- **Feature Management**: Three feature types (boolean, limit, consumable) with granular access control
- **Module System**: Organize features into logical modules with hierarchy support
- **Usage Tracking**: Track and meter feature consumption with automatic limit enforcement
- **Subscription Lifecycle**: Complete management of subscriptions (create, cancel, resume, pause, renew)
- **Plan Changes**: Upgrade, downgrade, or switch plans with proration support
- **Events System**: Comprehensive event system for subscription lifecycle and feature usage
- **Middleware & Gates**: Built-in middleware and gates for route protection
- **Soft Deletes**: Complete audit trails with soft delete support
- **UUID Support**: Secondary UUID identifiers for distributed systems
- **BigInt Primary Keys**: Scalable database design for millions of subscriptions

## Installation

You can install the package via composer:

```bash
composer require mohamedhabibwork/laravel-subscription
```

The package will automatically register its service provider.

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="laravel-subscription-migrations"
php artisan migrate
```

Publish the config file:

```bash
php artisan vendor:publish --tag="laravel-subscription-config"
```

## Quick Start

### 1. Add the Trait to Your Model

```php
use HSubscription\LaravelSubscription\Concerns\HasSubscriptions;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasSubscriptions;
}
```

### 2. Create a Plan

```php
use HSubscription\LaravelSubscription\Enums\BillingInterval;
use HSubscription\LaravelSubscription\Models\Plan;

$plan = Plan::create([
    'name' => 'Pro Plan',
    'slug' => 'pro',
    'price' => 29.99,
    'currency' => 'USD',
    'interval' => BillingInterval::Monthly,
    'trial_days' => 14,
]);
```

### 3. Create Features

```php
use HSubscription\LaravelSubscription\Models\Feature;

// Boolean feature (on/off)
$apiAccess = Feature::create([
    'name' => 'API Access',
    'slug' => 'api-access',
    'type' => 'boolean',
    'default_value' => 1,
]);

// Limit feature (maximum allowed)
$projects = Feature::create([
    'name' => 'Projects',
    'slug' => 'projects',
    'type' => 'limit',
    'default_value' => 10,
]);

// Consumable feature (depleting resource)
$apiCalls = Feature::create([
    'name' => 'API Calls',
    'slug' => 'api-calls',
    'type' => \HSubscription\LaravelSubscription\Enums\FeatureType::Consumable,
    'default_value' => 10000,
    'reset_period' => \HSubscription\LaravelSubscription\Enums\ResetPeriod::Monthly,
]);
```

### 4. Attach Features to Plan

```php
$plan->features()->attach([
    $apiAccess->id => ['value' => 1],
    $projects->id => ['value' => 50], // Override default
    $apiCalls->id => ['value' => 50000],
]);
```

### 5. Subscribe a User

```php
$user = User::find(1);
$subscription = $user->subscribe($plan);
```

### 6. Check Feature Access

```php
if ($user->hasFeature('api-access')) {
    // User has API access
}

if ($user->canUseFeature('api-calls', 100)) {
    $user->consumeFeature('api-calls', 100);
}

$remaining = $user->remainingFeatureUsage('api-calls');
```

## Core Concepts

### Plans

Plans define subscription tiers with pricing, billing intervals, and trial periods. Each plan can have multiple features and modules attached.

### Features

Features come in three types:

- **Boolean**: Simple on/off access (e.g., "API Access")
- **Limit**: Maximum allowed usage (e.g., "10 Projects")
- **Consumable**: Depleting resources that reset periodically (e.g., "10,000 API Calls per month")

### Modules

Modules organize features into logical groups. They can have parent-child relationships and can be enabled/disabled per plan.

### Subscriptions

Subscriptions link subscribers to plans. They track status (active, cancelled, expired, on_trial, past_due, paused) and lifecycle dates.

## Usage Examples

### Setting Up Plans, Features, and Modules

#### Creating Plans

```php
$basicPlan = Plan::create([
    'name' => 'Basic',
    'slug' => 'basic',
    'price' => 9.99,
    'currency' => 'USD',
    'interval' => 'monthly',
    'trial_days' => 7,
    'tier' => 'personal',
]);

$proPlan = Plan::create([
    'name' => 'Pro',
    'slug' => 'pro',
    'price' => 29.99,
    'currency' => 'USD',
    'interval' => 'monthly',
    'trial_days' => 14,
    'tier' => 'business',
]);
```

#### Creating Features

```php
// Boolean feature
$feature1 = Feature::create([
    'name' => 'Advanced Analytics',
    'slug' => 'advanced-analytics',
    'type' => 'boolean',
    'default_value' => 1,
]);

// Limit feature
$feature2 = Feature::create([
    'name' => 'Team Members',
    'slug' => 'team-members',
    'type' => 'limit',
    'default_value' => 5,
]);

// Consumable feature
$feature3 = Feature::create([
    'name' => 'Storage',
    'slug' => 'storage',
    'type' => 'consumable',
    'default_value' => 100, // GB
    'reset_period' => 'monthly',
]);
```

#### Creating Modules

```php
$analyticsModule = Module::create([
    'name' => 'Analytics',
    'slug' => 'analytics',
    'description' => 'Analytics and reporting features',
]);

// Child module
$advancedAnalytics = Module::create([
    'name' => 'Advanced Analytics',
    'slug' => 'advanced-analytics',
    'parent_id' => $analyticsModule->id,
]);

// Attach features to module
$analyticsModule->features()->attach($feature1->id);
```

#### Attaching Features and Modules to Plans

```php
// Attach features with custom values
$proPlan->features()->attach([
    $feature1->id => ['value' => 1],
    $feature2->id => ['value' => 20], // Override default
    $feature3->id => ['value' => 500],
]);

// Attach modules
$proPlan->modules()->attach([
    $analyticsModule->id => ['is_enabled' => true],
]);
```

### Subscriber Setup

#### Adding the Trait

```php
use HSubscription\LaravelSubscription\Concerns\HasSubscriptions;

class User extends Model
{
    use HasSubscriptions;
}

class Team extends Model
{
    use HasSubscriptions;
}
```

#### Creating Subscriptions

```php
// Basic subscription
$subscription = $user->subscribe($plan);

// With custom options
$subscription = $user->subscribe($plan, [
    'name' => 'main',
    'trial_days' => 30,
    'starts_at' => now()->addWeek(),
]);

// Multiple subscriptions
$user->subscribe($plan1, ['name' => 'main']);
$user->subscribe($plan2, ['name' => 'secondary']);
```

### Subscription Management

#### Checking Subscription Status

```php
// Check if user has active subscription
if ($user->active()) {
    // User has active subscription
}

// Check if on trial
if ($user->onTrial()) {
    // User is on trial
}

// Check if cancelled
if ($user->cancelled()) {
    // Subscription is cancelled
}

// Get specific subscription
$subscription = $user->subscription('main');

// Get all subscriptions
$allSubscriptions = $user->subscriptions;

// Get only active subscriptions
$activeSubscriptions = $user->activeSubscriptions;
```

#### Cancelling Subscriptions

```php
use HSubscription\LaravelSubscription\Services\SubscriptionService;

$service = app(SubscriptionService::class);
$subscription = $user->subscription();

// Cancel at end of period
$service->cancel($subscription, false);

// Cancel immediately
$service->cancel($subscription, true);
```

#### Resuming Subscriptions

```php
$service->resume($subscription);
```

#### Pausing/Unpausing

```php
// Pause subscription
$service->pause($subscription);

// Unpause subscription
$service->unpause($subscription);
```

#### Plan Changes

```php
$oldPlan = Plan::where('slug', 'basic')->first();
$newPlan = Plan::where('slug', 'pro')->first();

// Immediate plan change
$change = $service->changePlan($subscription, $newPlan, true);

// Scheduled plan change
$scheduledFor = now()->addWeek();
$change = $service->changePlan($subscription, $newPlan, false, $scheduledFor);
```

### Feature Access Control

#### Checking Feature Access

```php
// By feature instance
if ($user->hasFeature($feature)) {
    // User has access
}

// By feature slug
if ($user->hasFeature('api-access')) {
    // User has access
}

// By feature ID
if ($user->hasFeature(1)) {
    // User has access
}
```

#### Consuming Features

```php
// Check if can consume
if ($user->canUseFeature('api-calls', 100)) {
    // Consume feature
    $user->consumeFeature('api-calls', 100);
}

// Get remaining usage
$remaining = $user->remainingFeatureUsage('api-calls');

// Get feature value/limit
$limit = $user->featureValue('api-calls');
```

#### Feature Usage History

```php
$history = $user->featureUsageHistory('api-calls');
foreach ($history as $usage) {
    echo "Used: {$usage->used} / Limit: {$usage->limit}";
}
```

#### Custom Limits

```php
// Set custom limit
$user->setCustomLimit('api-calls', 100000);

// Check limit status
$status = $user->limitStatus('api-calls');
// Returns: ['limit' => 100000, 'used' => 5000, 'remaining' => 95000]

// Reset limits
$user->resetLimits();
```

### Module Management

#### Checking Module Access

```php
if ($user->hasModule('analytics')) {
    // User has access to analytics module
}
```

#### Activating/Deactivating Modules

```php
// Activate module
$user->activateModule('analytics');

// Deactivate module
$user->deactivateModule('analytics');

// Get active modules
$activeModules = $user->activeModules();

// Get module features
$features = $user->moduleFeatures('analytics');
```

### PHP 8 Attributes

The package provides PHP 8.1 attributes for declarative access control and configuration:

#### RequiresFeature Attribute

```php
use HSubscription\LaravelSubscription\Attributes\RequiresFeature;

class ApiController extends Controller
{
    #[RequiresFeature('api-access')]
    public function index()
    {
        // This method requires the 'api-access' feature
    }

    #[RequiresFeature('api-calls', 'main')]
    public function advanced()
    {
        // Requires 'api-calls' feature from 'main' subscription
    }
}
```

#### RequiresModule Attribute

```php
use HSubscription\LaravelSubscription\Attributes\RequiresModule;

class AnalyticsController extends Controller
{
    #[RequiresModule('analytics')]
    public function dashboard()
    {
        // This method requires the 'analytics' module
    }
}
```

#### RequiresSubscription Attribute

```php
use HSubscription\LaravelSubscription\Attributes\RequiresSubscription;

class PremiumController extends Controller
{
    #[RequiresSubscription('default', allowTrial: true)]
    public function premiumFeature()
    {
        // Requires active subscription, allows trial
    }

    #[RequiresSubscription('main', allowTrial: false)]
    public function paidOnly()
    {
        // Requires paid subscription (no trial)
    }
}
```

**Note:** Attributes are provided for convenience. You'll need to implement middleware or route filters to enforce them. The package provides middleware classes that can be used for this purpose.

### Middleware Usage

#### Route Protection with Features

```php
// In routes/web.php or routes/api.php
Route::middleware(['subscription.feature:api-access'])->group(function () {
    Route::get('/api', [ApiController::class, 'index']);
});

// With custom subscription name
Route::middleware(['subscription.feature:api-access,main'])->group(function () {
    Route::get('/api', [ApiController::class, 'index']);
});
```

#### Route Protection with Modules

```php
Route::middleware(['subscription.module:analytics'])->group(function () {
    Route::get('/analytics', [AnalyticsController::class, 'index']);
});
```

#### Additional Subscription Middleware

The package provides additional middleware for subscription status checks:

```php
// Ensure subscription is active
Route::middleware(['subscription.active'])->group(function () {
    Route::get('/premium', [PremiumController::class, 'index']);
});

// Ensure subscription is not cancelled
Route::middleware(['subscription.not-cancelled'])->group(function () {
    Route::get('/billing', [BillingController::class, 'index']);
});

// Ensure subscription is not expired
Route::middleware(['subscription.not-expired'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

// With custom subscription name
Route::middleware(['subscription.active:main'])->group(function () {
    Route::get('/enterprise', [EnterpriseController::class, 'index']);
});
```

#### Using Gates

```php
// In your controller or policy
if (Gate::allows('use-feature', 'api-access')) {
    // User can use the feature
}

if (Gate::allows('use-module', 'analytics')) {
    // User can use the module
}

if (Gate::allows('consume-feature', ['api-calls', 100])) {
    // User can consume 100 API calls
}
```

### Events

#### Available Events

- `SubscriptionCreatedEvent`
- `SubscriptionCancelledEvent`
- `SubscriptionResumedEvent`
- `SubscriptionExpiredEvent`
- `SubscriptionRenewedEvent`
- `SubscriptionDeletedEvent`
- `PlanChangedEvent`
- `TrialEndingEvent`
- `TrialEndedEvent`
- `FeatureLimitReachedEvent`
- `FeatureLimitExceededEvent`
- `ModuleActivatedEvent`
- `ModuleDeactivatedEvent`
- `UsageRecordedEvent`
- `LimitResetEvent`

#### Event Listener Example

```php
use HSubscription\LaravelSubscription\Events\SubscriptionCreatedEvent;
use Illuminate\Support\Facades\Event;

Event::listen(SubscriptionCreatedEvent::class, function ($event) {
    $subscription = $event->subscription;
    
    // Send welcome email
    Mail::to($subscription->subscribable->email)->send(new WelcomeEmail());
    
    // Activate plan modules
    $moduleService = app(\HSubscription\LaravelSubscription\Services\ModuleService::class);
    $moduleService->activatePlanModules($subscription);
});
```

#### Registering Listeners

```php
// In EventServiceProvider
protected $listen = [
    \HSubscription\LaravelSubscription\Events\SubscriptionCreatedEvent::class => [
        \App\Listeners\SendWelcomeEmail::class,
    ],
    \HSubscription\LaravelSubscription\Events\FeatureLimitReachedEvent::class => [
        \App\Listeners\NotifyLimitReached::class,
    ],
];
```

## Configuration

The configuration file includes the following options:

```php
return [
    'default_currency' => 'USD',
    'default_trial_days' => 0,
    'default_grace_days' => 0,
    'proration_behavior' => 'time_based',
    'usage_reset_schedule' => '0 0 * * *',
    'overage_policy' => 'block',
    'overage_fee_multiplier' => 1.5,
    'feature_inheritance_on_plan_change' => true,
    'module_inheritance_on_plan_change' => true,
    'default_feature_value' => 0,
    'limit_enforcement' => 'strict',
    'module_activation_rule' => 'plan_based',
    'soft_delete_retention_days' => 90,
    'uuid_generation' => 'uuid',
    'date_format' => 'Y-m-d H:i:s',
    'enable_tax_calculation' => false,
    'default_tax_rate' => 0,
    'trial_ending_notification_days' => 7,
];
```

## API Reference

### HasSubscriptions Trait Methods

#### Subscription Methods

- `subscribe(Plan|int|string $plan, array $options = [])`: Create a new subscription
- `subscription(?string $name = 'default')`: Get a specific subscription by name
- `subscriptions()`: Get all subscriptions (morphMany relationship)
- `activeSubscriptions()`: Get only active subscriptions
- `subscribedTo(Plan|int|string $plan, ?string $name = 'default')`: Check if subscribed to a plan
- `onTrial(?string $name = 'default')`: Check if subscription is on trial
- `cancelled(?string $name = 'default')`: Check if subscription is cancelled
- `active(?string $name = 'default')`: Check if subscription is active

#### Feature Methods

- `hasFeature(Feature|int|string $feature, ?string $subscriptionName = 'default')`: Check feature access
- `canUseFeature(Feature|int|string $feature, int $amount = 1, ?string $subscriptionName = 'default')`: Check if can consume feature
- `consumeFeature(Feature|int|string $feature, int $amount = 1, ?string $subscriptionName = 'default')`: Record feature usage
- `remainingFeatureUsage(Feature|int|string $feature, ?string $subscriptionName = 'default')`: Get remaining usage
- `featureValue(Feature|int|string $feature, ?string $subscriptionName = 'default')`: Get feature limit/value
- `featureUsageHistory(Feature|int|string $feature, ?string $subscriptionName = 'default')`: Get usage history

#### Module Methods

- `hasModule(Module|int|string $module, ?string $subscriptionName = 'default')`: Check module access
- `activeModules(?string $subscriptionName = 'default')`: Get all active modules
- `moduleFeatures(Module|int|string $module, ?string $subscriptionName = 'default')`: Get features in module
- `activateModule(Module|int|string $module, ?string $subscriptionName = 'default')`: Activate module
- `deactivateModule(Module|int|string $module, ?string $subscriptionName = 'default')`: Deactivate module

#### Limit Methods

- `checkLimit(Feature|int|string $feature, ?string $subscriptionName = 'default')`: Validate against limit
- `setCustomLimit(Feature|int|string $feature, int $limit, ?string $subscriptionName = 'default')`: Override plan limit
- `resetLimits(?string $subscriptionName = 'default')`: Reset all usage counters
- `limitStatus(Feature|int|string $feature, ?string $subscriptionName = 'default')`: Get limit status

### Service Classes

#### SubscriptionService

- `create(Model $subscriber, Plan|int|string $plan, array $options = [])`: Create subscription
- `changePlan(Subscription $subscription, Plan|int|string $newPlan, bool $immediate = true, ?\DateTime $scheduledFor = null)`: Change plan
- `cancel(Subscription $subscription, bool $immediately = false)`: Cancel subscription
- `resume(Subscription $subscription)`: Resume cancelled subscription
- `pause(Subscription $subscription)`: Pause subscription
- `unpause(Subscription $subscription)`: Unpause subscription
- `renew(Subscription $subscription)`: Renew subscription
- `expire(Subscription $subscription)`: Expire subscription
- `convertTrialToPaid(Subscription $subscription)`: Convert trial to paid
- `forceDelete(Subscription $subscription)`: Permanently delete subscription

#### FeatureService

- `hasAccess(Model $subscriber, Feature|int|string $feature, ?string $subscriptionName = 'default')`: Check access
- `canConsume(Model $subscriber, Feature|int|string $feature, int $amount = 1, ?string $subscriptionName = 'default')`: Check if can consume
- `consume(Model $subscriber, Feature|int|string $feature, int $amount = 1, ?string $subscriptionName = 'default')`: Consume feature
- `getRemainingUsage(Subscription $subscription, Feature|int|string $feature)`: Get remaining usage
- `getFeatureLimit(Subscription $subscription, Feature|int|string $feature)`: Get feature limit
- `setCustomLimit(Subscription $subscription, Feature|int|string $feature, int $limit, string $limitType = 'hard', ?int $warningThreshold = null)`: Set custom limit
- `resetUsage(Subscription $subscription, Feature|int|string|null $feature = null)`: Reset usage

#### ModuleService

- `hasAccess(Model $subscriber, Module|int|string $module, ?string $subscriptionName = 'default')`: Check module access
- `activate(Subscription $subscription, Module|int|string $module)`: Activate module
- `deactivate(Subscription $subscription, Module|int|string $module)`: Deactivate module
- `getActiveModules(Subscription $subscription)`: Get active modules
- `getModuleFeatures(Subscription $subscription, Module|int|string $module)`: Get module features
- `activatePlanModules(Subscription $subscription)`: Activate all plan modules

## Advanced Usage

### Polymorphic Subscribers

Any model can be a subscriber:

```php
class User extends Model
{
    use HasSubscriptions;
}

class Team extends Model
{
    use HasSubscriptions;
}

class Organization extends Model
{
    use HasSubscriptions;
}

// All can have subscriptions
$user->subscribe($plan);
$team->subscribe($plan);
$organization->subscribe($plan);
```

### Custom Subscription Names

Use custom names for multiple subscriptions:

```php
$user->subscribe($plan1, ['name' => 'main']);
$user->subscribe($plan2, ['name' => 'addon']);

$mainSubscription = $user->subscription('main');
$addonSubscription = $user->subscription('addon');
```

### Proration Calculations

Proration is automatically calculated when changing plans:

```php
$change = $service->changePlan($subscription, $newPlan);
// $change->proration_amount contains the prorated amount
```

### Usage Reset Strategies

Features can reset based on different periods:

```php
// Never reset
$feature->update(['reset_period' => 'never']);

// Reset daily
$feature->update(['reset_period' => 'daily']);

// Reset monthly
$feature->update(['reset_period' => 'monthly']);

// Reset yearly
$feature->update(['reset_period' => 'yearly']);
```

### Soft Delete Recovery

All models support soft deletes for audit trails:

```php
// Soft delete
$subscription->delete();

// Restore
$subscription->restore();

// Check if trashed
if ($subscription->trashed()) {
    // Subscription is soft deleted
}

// Include trashed in queries
$subscriptions = Subscription::withTrashed()->get();
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Mohamed Habib](https://github.com/mohamedhabibwork)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
