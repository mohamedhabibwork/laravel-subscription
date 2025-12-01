<?php

namespace HSubscription\LaravelSubscription\Concerns;

use HSubscription\LaravelSubscription\Enums\LimitType;
use HSubscription\LaravelSubscription\Enums\ResetPeriod;
use HSubscription\LaravelSubscription\Enums\SubscriptionStatus;
use HSubscription\LaravelSubscription\Models\Feature;
use HSubscription\LaravelSubscription\Models\Module;
use HSubscription\LaravelSubscription\Models\Plan;
use HSubscription\LaravelSubscription\Models\Subscription;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasSubscriptions
{
    public function subscriptions(): MorphMany
    {
        return $this->morphMany(Subscription::class, 'subscribable');
    }

    public function activeSubscriptions(): MorphMany
    {
        return $this->subscriptions()->where('status', SubscriptionStatus::Active->value);
    }

    public function subscribe(Plan|int|string $plan, array $options = []): Subscription
    {
        $plan = $this->resolvePlan($plan);
        $name = $options['name'] ?? 'default';
        $trialDays = $options['trial_days'] ?? $plan->trial_days;

        $subscription = $this->subscriptions()->create([
            'plan_id' => $plan->id,
            'name' => $name,
            'status' => $trialDays > 0 ? SubscriptionStatus::OnTrial : SubscriptionStatus::Active,
            'trial_ends_at' => $trialDays > 0 ? now()->addDays($trialDays) : null,
            'starts_at' => $options['starts_at'] ?? now(),
            'ends_at' => $options['ends_at'] ?? null,
            'metadata' => $options['metadata'] ?? [],
        ]);

        return $subscription;
    }

    public function subscription(?string $name = 'default'): ?Subscription
    {
        return $this->subscriptions()
            ->where('name', $name)
            ->latest()
            ->first();
    }

    public function subscribedTo(Plan|int|string $plan, ?string $name = 'default'): bool
    {
        $plan = $this->resolvePlan($plan);

        return $this->subscriptions()
            ->where('plan_id', $plan->id)
            ->where('name', $name)
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::OnTrial])
            ->exists();
    }

    public function onTrial(?string $name = 'default'): bool
    {
        return $this->subscription($name)?->isOnTrial() ?? false;
    }

    public function cancelled(?string $name = 'default'): bool
    {
        return $this->subscription($name)?->isCancelled() ?? false;
    }

    public function active(?string $name = 'default'): bool
    {
        return $this->subscription($name)?->isActive() ?? false;
    }

    public function hasFeature(Feature|int|string $feature, ?string $subscriptionName = 'default'): bool
    {
        $subscription = $this->subscription($subscriptionName);

        if (! $subscription || ! $subscription->isActive() && ! $subscription->isOnTrial()) {
            return false;
        }

        $feature = $this->resolveFeature($feature);

        // Check if plan has this feature
        $planFeature = $subscription->plan->features()
            ->where('features.id', $feature->id)
            ->first();

        if (! $planFeature) {
            return false;
        }

        // For boolean features, just check if it exists
        if ($feature->isBoolean()) {
            return true;
        }

        // For limit and consumable features, check if there's remaining usage
        return $this->remainingFeatureUsage($feature, $subscriptionName) > 0;
    }

    public function hasModule(Module|int|string $module, ?string $subscriptionName = 'default'): bool
    {
        $subscription = $this->subscription($subscriptionName);

        if (! $subscription || ! $subscription->isActive() && ! $subscription->isOnTrial()) {
            return false;
        }

        $module = $this->resolveModule($module);

        // Check if plan has this module enabled
        $planModule = $subscription->plan->modules()
            ->where('modules.id', $module->id)
            ->where('plan_module.is_enabled', true)
            ->first();

        if (! $planModule) {
            return false;
        }

        // Check if module is activated for this subscription
        return $subscription->moduleActivations()
            ->where('module_id', $module->id)
            ->where('is_active', true)
            ->exists();
    }

    public function canUseFeature(Feature|int|string $feature, int $amount = 1, ?string $subscriptionName = 'default'): bool
    {
        if (! $this->hasFeature($feature, $subscriptionName)) {
            return false;
        }

        $feature = $this->resolveFeature($feature);

        // Boolean features don't have usage limits
        if ($feature->isBoolean()) {
            return true;
        }

        $remaining = $this->remainingFeatureUsage($feature, $subscriptionName);

        return $remaining >= $amount;
    }

    public function consumeFeature(Feature|int|string $feature, int $amount = 1, ?string $subscriptionName = 'default'): bool
    {
        if (! $this->canUseFeature($feature, $amount, $subscriptionName)) {
            return false;
        }

        $subscription = $this->subscription($subscriptionName);
        $feature = $this->resolveFeature($feature);

        // Get or create usage record
        $usage = $subscription->usage()
            ->where('feature_id', $feature->id)
            ->valid()
            ->first();

        if (! $usage) {
            $limit = $this->getFeatureLimit($feature, $subscription);
            $validUntil = $this->calculateValidUntil($feature, $subscription);

            $usage = $subscription->usage()->create([
                'feature_id' => $feature->id,
                'used' => 0,
                'limit' => $limit,
                'valid_until' => $validUntil,
            ]);
        }

        $usage->increment('used', $amount);

        return true;
    }

    public function remainingFeatureUsage(Feature|int|string $feature, ?string $subscriptionName = 'default'): int
    {
        $subscription = $this->subscription($subscriptionName);

        if (! $subscription) {
            return 0;
        }

        $feature = $this->resolveFeature($feature);

        if ($feature->isBoolean()) {
            return $this->hasFeature($feature, $subscriptionName) ? 1 : 0;
        }

        $usage = $subscription->usage()
            ->where('feature_id', $feature->id)
            ->valid()
            ->first();

        if (! $usage) {
            $limit = $this->getFeatureLimit($feature, $subscription);

            return max(0, $limit);
        }

        $limit = $usage->limit ?? $this->getFeatureLimit($feature, $subscription);

        return max(0, $limit - $usage->used);
    }

    public function featureValue(Feature|int|string $feature, ?string $subscriptionName = 'default'): ?int
    {
        $subscription = $this->subscription($subscriptionName);

        if (! $subscription) {
            return null;
        }

        return $this->getFeatureLimit($feature, $subscription);
    }

    public function featureUsageHistory(Feature|int|string $feature, ?string $subscriptionName = 'default')
    {
        $subscription = $this->subscription($subscriptionName);

        if (! $subscription) {
            return collect();
        }

        $feature = $this->resolveFeature($feature);

        return $subscription->usage()
            ->where('feature_id', $feature->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function activeModules(?string $subscriptionName = 'default')
    {
        $subscription = $this->subscription($subscriptionName);

        if (! $subscription) {
            return collect();
        }

        return $subscription->moduleActivations()
            ->where('is_active', true)
            ->with('module')
            ->get()
            ->pluck('module');
    }

    public function moduleFeatures(Module|int|string $module, ?string $subscriptionName = 'default')
    {
        if (! $this->hasModule($module, $subscriptionName)) {
            return collect();
        }

        $module = $this->resolveModule($module);

        return $module->features()->active()->get();
    }

    public function activateModule(Module|int|string $module, ?string $subscriptionName = 'default'): bool
    {
        if (! $this->hasModule($module, $subscriptionName)) {
            return false;
        }

        $subscription = $this->subscription($subscriptionName);
        $module = $this->resolveModule($module);

        $activation = $subscription->moduleActivations()
            ->where('module_id', $module->id)
            ->first();

        if ($activation) {
            $activation->update([
                'is_active' => true,
                'activated_at' => now(),
                'deactivated_at' => null,
            ]);
        } else {
            $subscription->moduleActivations()->create([
                'module_id' => $module->id,
                'is_active' => true,
                'activated_at' => now(),
            ]);
        }

        return true;
    }

    public function deactivateModule(Module|int|string $module, ?string $subscriptionName = 'default'): bool
    {
        $subscription = $this->subscription($subscriptionName);

        if (! $subscription) {
            return false;
        }

        $module = $this->resolveModule($module);

        $activation = $subscription->moduleActivations()
            ->where('module_id', $module->id)
            ->first();

        if ($activation) {
            $activation->update([
                'is_active' => false,
                'deactivated_at' => now(),
            ]);

            return true;
        }

        return false;
    }

    public function checkLimit(Feature|int|string $feature, ?string $subscriptionName = 'default'): bool
    {
        return $this->canUseFeature($feature, 1, $subscriptionName);
    }

    public function setCustomLimit(Feature|int|string $feature, int $limit, ?string $subscriptionName = 'default'): bool
    {
        $subscription = $this->subscription($subscriptionName);

        if (! $subscription) {
            return false;
        }

        $feature = $this->resolveFeature($feature);

        $subscriptionLimit = $subscription->limits()
            ->where('feature_id', $feature->id)
            ->first();

        if ($subscriptionLimit) {
            $subscriptionLimit->update(['custom_limit' => $limit]);
        } else {
            $subscription->limits()->create([
                'feature_id' => $feature->id,
                'custom_limit' => $limit,
                'limit_type' => LimitType::Hard,
            ]);
        }

        return true;
    }

    public function resetLimits(?string $subscriptionName = 'default'): bool
    {
        $subscription = $this->subscription($subscriptionName);

        if (! $subscription) {
            return false;
        }

        $subscription->usage()->update([
            'used' => 0,
            'reset_at' => now(),
        ]);

        return true;
    }

    public function limitStatus(Feature|int|string $feature, ?string $subscriptionName = 'default'): array
    {
        $subscription = $this->subscription($subscriptionName);

        if (! $subscription) {
            return [
                'limit' => 0,
                'used' => 0,
                'remaining' => 0,
            ];
        }

        $feature = $this->resolveFeature($feature);
        $limit = $this->getFeatureLimit($feature, $subscription);

        $usage = $subscription->usage()
            ->where('feature_id', $feature->id)
            ->valid()
            ->first();

        $used = $usage?->used ?? 0;

        return [
            'limit' => $limit,
            'used' => $used,
            'remaining' => max(0, $limit - $used),
        ];
    }

    protected function resolvePlan(Plan|int|string $plan): Plan
    {
        if ($plan instanceof Plan) {
            return $plan;
        }

        if (is_numeric($plan)) {
            return Plan::findOrFail($plan);
        }

        return Plan::where('slug', $plan)->firstOrFail();
    }

    protected function resolveFeature(Feature|int|string $feature): Feature
    {
        if ($feature instanceof Feature) {
            return $feature;
        }

        if (is_numeric($feature)) {
            return Feature::findOrFail($feature);
        }

        return Feature::where('slug', $feature)->firstOrFail();
    }

    protected function resolveModule(Module|int|string $module): Module
    {
        if ($module instanceof Module) {
            return $module;
        }

        if (is_numeric($module)) {
            return Module::findOrFail($module);
        }

        return Module::where('slug', $module)->firstOrFail();
    }

    protected function getFeatureLimit(Feature|int|string $feature, Subscription $subscription): int
    {
        $feature = $this->resolveFeature($feature);

        // Check for custom limit override
        $customLimit = $subscription->limits()
            ->where('feature_id', $feature->id)
            ->first();

        if ($customLimit && $customLimit->custom_limit !== null) {
            return $customLimit->custom_limit;
        }

        // Get limit from plan feature pivot
        $planFeature = $subscription->plan->features()
            ->where('features.id', $feature->id)
            ->first();

        if ($planFeature && $planFeature->pivot->value !== null) {
            return $planFeature->pivot->value;
        }

        // Fall back to feature default value
        return $feature->default_value;
    }

    protected function calculateValidUntil(Feature $feature, Subscription $subscription): ?\DateTime
    {
        if (! $feature->reset_period || $feature->reset_period->isNever()) {
            return null;
        }

        $now = now();

        return match ($feature->reset_period) {
            ResetPeriod::Daily => $now->copy()->endOfDay(),
            ResetPeriod::Monthly => $now->copy()->endOfMonth(),
            ResetPeriod::Yearly => $now->copy()->endOfYear(),
            default => null,
        };
    }
}
