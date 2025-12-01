<?php

namespace HSubscription\LaravelSubscription\Services;

use HSubscription\LaravelSubscription\Enums\LimitType;
use HSubscription\LaravelSubscription\Enums\ResetPeriod;
use HSubscription\LaravelSubscription\Events\FeatureLimitExceededEvent;
use HSubscription\LaravelSubscription\Events\FeatureLimitReachedEvent;
use HSubscription\LaravelSubscription\Events\UsageRecordedEvent;
use HSubscription\LaravelSubscription\Models\Feature;
use HSubscription\LaravelSubscription\Models\Subscription;
use HSubscription\LaravelSubscription\Models\SubscriptionLimit;
use HSubscription\LaravelSubscription\Models\SubscriptionUsage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FeatureService
{
    public function hasAccess(Model $subscriber, Feature|int|string $feature, ?string $subscriptionName = 'default'): bool
    {
        $subscription = $subscriber->subscription($subscriptionName);

        if (! $subscription || (! $subscription->isActive() && ! $subscription->isOnTrial())) {
            return false;
        }

        $feature = $this->resolveFeature($feature);

        // Check if plan has this feature
        $planFeature = $subscription->plan->features()
            ->where('features.id', $feature->id)
            ->first();

        return $planFeature !== null;
    }

    public function canConsume(Model $subscriber, Feature|int|string $feature, int $amount = 1, ?string $subscriptionName = 'default'): bool
    {
        if (! $this->hasAccess($subscriber, $feature, $subscriptionName)) {
            return false;
        }

        $subscription = $subscriber->subscription($subscriptionName);
        $feature = $this->resolveFeature($feature);

        // Boolean features don't have usage limits
        if ($feature->isBoolean()) {
            return true;
        }

        $remaining = $this->getRemainingUsage($subscription, $feature);

        return $remaining >= $amount;
    }

    public function consume(Model $subscriber, Feature|int|string $feature, int $amount = 1, ?string $subscriptionName = 'default'): bool
    {
        return DB::transaction(function () use ($subscriber, $feature, $amount, $subscriptionName) {
            if (! $this->canConsume($subscriber, $feature, $amount, $subscriptionName)) {
                $subscription = $subscriber->subscription($subscriptionName);
                $feature = $this->resolveFeature($feature);

                event(new FeatureLimitExceededEvent($subscription, $feature, $amount));

                return false;
            }

            $subscription = $subscriber->subscription($subscriptionName);
            $feature = $this->resolveFeature($feature);

            $usage = $this->getOrCreateUsage($subscription, $feature);
            $oldUsed = $usage->used;
            $usage->increment('used', $amount);

            // Check if limit reached
            $limit = $this->getFeatureLimit($subscription, $feature);
            if ($limit > 0 && $usage->used >= $limit) {
                event(new FeatureLimitReachedEvent($subscription, $feature, $usage->used));
            }

            event(new UsageRecordedEvent($subscription, $feature, $amount, $oldUsed, $usage->used));

            return true;
        });
    }

    public function getRemainingUsage(Subscription $subscription, Feature|int|string $feature): int
    {
        $feature = $this->resolveFeature($feature);

        if ($feature->isBoolean()) {
            return $this->hasAccess($subscription->subscribable, $feature) ? 1 : 0;
        }

        $usage = $subscription->usage()
            ->where('feature_id', $feature->id)
            ->valid()
            ->first();

        if (! $usage) {
            $limit = $this->getFeatureLimit($subscription, $feature);

            return max(0, $limit);
        }

        $limit = $usage->limit ?? $this->getFeatureLimit($subscription, $feature);

        return max(0, $limit - $usage->used);
    }

    public function getFeatureLimit(Subscription $subscription, Feature|int|string $feature): int
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

    public function setCustomLimit(Subscription $subscription, Feature|int|string $feature, int $limit, LimitType|string $limitType = LimitType::Hard, ?int $warningThreshold = null): SubscriptionLimit
    {
        $feature = $this->resolveFeature($feature);

        if (is_string($limitType)) {
            $limitType = LimitType::from($limitType);
        }

        $subscriptionLimit = $subscription->limits()
            ->where('feature_id', $feature->id)
            ->first();

        if ($subscriptionLimit) {
            $subscriptionLimit->update([
                'custom_limit' => $limit,
                'limit_type' => $limitType,
                'warning_threshold' => $warningThreshold,
            ]);

            return $subscriptionLimit->fresh();
        }

        return $subscription->limits()->create([
            'feature_id' => $feature->id,
            'custom_limit' => $limit,
            'limit_type' => $limitType,
            'warning_threshold' => $warningThreshold,
        ]);
    }

    public function resetUsage(Subscription $subscription, Feature|int|string|null $feature = null): void
    {
        if ($feature) {
            $feature = $this->resolveFeature($feature);
            $subscription->usage()
                ->where('feature_id', $feature->id)
                ->update([
                    'used' => 0,
                    'reset_at' => now(),
                ]);
        } else {
            $subscription->usage()->update([
                'used' => 0,
                'reset_at' => now(),
            ]);
        }
    }

    public function resetUsageByPeriod(Subscription $subscription, Feature $feature): void
    {
        $validUntil = $this->calculateValidUntil($feature, $subscription);

        // Expire old usage records
        $subscription->usage()
            ->where('feature_id', $feature->id)
            ->where('valid_until', '<', now())
            ->update(['valid_until' => now()]);

        // Create new usage record for new period
        $limit = $this->getFeatureLimit($subscription, $feature);

        $subscription->usage()->create([
            'feature_id' => $feature->id,
            'used' => 0,
            'limit' => $limit,
            'valid_until' => $validUntil,
            'reset_at' => now(),
        ]);
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

    protected function getOrCreateUsage(Subscription $subscription, Feature $feature): SubscriptionUsage
    {
        $usage = $subscription->usage()
            ->where('feature_id', $feature->id)
            ->valid()
            ->first();

        if (! $usage) {
            $limit = $this->getFeatureLimit($subscription, $feature);
            $validUntil = $this->calculateValidUntil($feature, $subscription);

            $usage = $subscription->usage()->create([
                'feature_id' => $feature->id,
                'used' => 0,
                'limit' => $limit,
                'valid_until' => $validUntil,
            ]);
        }

        return $usage;
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
