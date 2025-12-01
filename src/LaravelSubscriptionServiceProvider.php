<?php

namespace HSubscription\LaravelSubscription;

use HSubscription\LaravelSubscription\Commands\LaravelSubscriptionCommand;
use HSubscription\LaravelSubscription\Http\Middleware\EnsureHasFeature;
use HSubscription\LaravelSubscription\Http\Middleware\EnsureHasModule;
use HSubscription\LaravelSubscription\Http\Middleware\EnsureSubscriptionActive;
use HSubscription\LaravelSubscription\Http\Middleware\EnsureSubscriptionNotCancelled;
use HSubscription\LaravelSubscription\Http\Middleware\EnsureSubscriptionNotExpired;
use HSubscription\LaravelSubscription\Services\FeatureService;
use HSubscription\LaravelSubscription\Services\ModuleService;
use HSubscription\LaravelSubscription\Services\SubscriptionService;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelSubscriptionServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-subscription')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations([
                '2024_01_01_000001_create_plans_table',
                '2024_01_01_000002_create_modules_table',
                '2024_01_01_000003_create_features_table',
                '2024_01_01_000004_create_plan_feature_table',
                '2024_01_01_000005_create_plan_module_table',
                '2024_01_01_000006_create_subscriptions_table',
                '2024_01_01_000007_create_subscription_usage_table',
                '2024_01_01_000008_create_subscription_limits_table',
                '2024_01_01_000009_create_module_activations_table',
                '2024_01_01_000010_create_subscription_changes_table',
            ])
            ->hasCommand(LaravelSubscriptionCommand::class);
    }

    public function packageRegistered(): void
    {
        // Register services as singletons
        $this->app->singleton(SubscriptionService::class);
        $this->app->singleton(FeatureService::class);
        $this->app->singleton(ModuleService::class);

        // Validate configuration on boot
        if (config('subscription.validate_config', true)) {
            $this->validateConfig();
        }
    }

    protected function validateConfig(): void
    {
        $errors = \HSubscription\LaravelSubscription\Helpers\ConfigHelper::validate();

        if (! empty($errors)) {
            throw new \RuntimeException('Subscription configuration errors: '.implode('; ', $errors));
        }
    }

    public function packageBooted(): void
    {
        // Register middleware aliases
        $router = $this->app['router'];
        $router->aliasMiddleware('subscription.feature', EnsureHasFeature::class);
        $router->aliasMiddleware('subscription.module', EnsureHasModule::class);
        $router->aliasMiddleware('subscription.active', EnsureSubscriptionActive::class);
        $router->aliasMiddleware('subscription.not-cancelled', EnsureSubscriptionNotCancelled::class);
        $router->aliasMiddleware('subscription.not-expired', EnsureSubscriptionNotExpired::class);
        $router->aliasMiddleware('hasFeature', EnsureHasFeature::class);
        $router->aliasMiddleware('hasModule', EnsureHasModule::class);

        // Register gates for feature and module access
        Gate::define('use-feature', function ($user, $feature, $subscriptionName = 'default') {
            if (! method_exists($user, 'hasFeature')) {
                return false;
            }

            return $user->hasFeature($feature, $subscriptionName);
        });

        Gate::define('use-module', function ($user, $module, $subscriptionName = 'default') {
            if (! method_exists($user, 'hasModule')) {
                return false;
            }

            return $user->hasModule($module, $subscriptionName);
        });

        Gate::define('consume-feature', function ($user, $feature, $amount = 1, $subscriptionName = 'default') {
            if (! method_exists($user, 'canUseFeature')) {
                return false;
            }

            return $user->canUseFeature($feature, $amount, $subscriptionName);
        });
    }
}
