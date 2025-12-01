<?php

namespace HSubscription\LaravelSubscription\Services;

use HSubscription\LaravelSubscription\Enums\BillingInterval;
use HSubscription\LaravelSubscription\Enums\PlanChangeType;
use HSubscription\LaravelSubscription\Enums\SubscriptionStatus;
use HSubscription\LaravelSubscription\Events\PlanChangedEvent;
use HSubscription\LaravelSubscription\Events\SubscriptionCancelledEvent;
use HSubscription\LaravelSubscription\Events\SubscriptionCreatedEvent;
use HSubscription\LaravelSubscription\Events\SubscriptionDeletedEvent;
use HSubscription\LaravelSubscription\Events\SubscriptionExpiredEvent;
use HSubscription\LaravelSubscription\Events\SubscriptionRenewedEvent;
use HSubscription\LaravelSubscription\Events\SubscriptionResumedEvent;
use HSubscription\LaravelSubscription\Events\TrialEndedEvent;
use HSubscription\LaravelSubscription\Models\Plan;
use HSubscription\LaravelSubscription\Models\Subscription;
use HSubscription\LaravelSubscription\Models\SubscriptionChange;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function create(Model $subscriber, Plan|int|string $plan, array $options = []): Subscription
    {
        return DB::transaction(function () use ($subscriber, $plan, $options) {
            $plan = $this->resolvePlan($plan);
            $name = $options['name'] ?? 'default';
            $trialDays = $options['trial_days'] ?? $plan->trial_days;

            $subscription = $subscriber->subscriptions()->create([
                'plan_id' => $plan->id,
                'name' => $name,
                'status' => $trialDays > 0 ? SubscriptionStatus::OnTrial : SubscriptionStatus::Active,
                'trial_ends_at' => $trialDays > 0 ? now()->addDays($trialDays) : null,
                'starts_at' => $options['starts_at'] ?? now(),
                'ends_at' => $options['ends_at'] ?? null,
                'metadata' => $options['metadata'] ?? [],
            ]);

            event(new SubscriptionCreatedEvent($subscription));

            return $subscription;
        });
    }

    public function changePlan(Subscription $subscription, Plan|int|string $newPlan, bool $immediate = true, ?\DateTime $scheduledFor = null): SubscriptionChange
    {
        return DB::transaction(function () use ($subscription, $newPlan, $immediate, $scheduledFor) {
            $oldPlan = $subscription->plan;
            $newPlan = $this->resolvePlan($newPlan);

            $changeType = $this->determineChangeType($oldPlan, $newPlan);
            $prorationAmount = $immediate ? $this->calculateProration($subscription, $oldPlan, $newPlan) : null;

            $change = $subscription->changes()->create([
                'from_plan_id' => $oldPlan->id,
                'to_plan_id' => $newPlan->id,
                'change_type' => $changeType,
                'is_immediate' => $immediate,
                'scheduled_for' => $scheduledFor,
                'proration_amount' => $prorationAmount,
            ]);

            if ($immediate) {
                $this->applyPlanChange($subscription, $newPlan, $change);
            }

            event(new PlanChangedEvent($subscription, $oldPlan, $newPlan, $change));

            return $change;
        });
    }

    public function cancel(Subscription $subscription, bool $immediately = false): Subscription
    {
        return DB::transaction(function () use ($subscription, $immediately) {
            if ($immediately) {
                $subscription->update([
                    'status' => SubscriptionStatus::Cancelled,
                    'cancelled_at' => now(),
                    'ends_at' => now(),
                ]);
            } else {
                $subscription->update([
                    'status' => SubscriptionStatus::Cancelled,
                    'cancelled_at' => now(),
                    'ends_at' => $subscription->ends_at ?? $subscription->trial_ends_at,
                ]);
            }

            event(new SubscriptionCancelledEvent($subscription));

            return $subscription->fresh();
        });
    }

    public function resume(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            if (! $subscription->isCancelled()) {
                throw new \Exception('Subscription is not cancelled and cannot be resumed.');
            }

            $subscription->update([
                'status' => SubscriptionStatus::Active,
                'cancelled_at' => null,
                'resumed_at' => now(),
            ]);

            event(new SubscriptionResumedEvent($subscription));

            return $subscription->fresh();
        });
    }

    public function pause(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            if (! $subscription->isActive() && ! $subscription->isOnTrial()) {
                throw new \Exception('Only active or trial subscriptions can be paused.');
            }

            $subscription->update([
                'status' => SubscriptionStatus::Paused,
                'paused_at' => now(),
            ]);

            return $subscription->fresh();
        });
    }

    public function unpause(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            if (! $subscription->isPaused()) {
                throw new \Exception('Subscription is not paused and cannot be unpaused.');
            }

            $subscription->update([
                'status' => SubscriptionStatus::Active,
                'paused_at' => null,
                'resumed_at' => now(),
            ]);

            event(new SubscriptionResumedEvent($subscription));

            return $subscription->fresh();
        });
    }

    public function renew(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            $plan = $subscription->plan;
            $interval = $plan->interval;
            $intervalCount = $plan->interval_count;

            $newEndsAt = match ($interval) {
                BillingInterval::Daily => now()->addDays($intervalCount),
                BillingInterval::Weekly => now()->addWeeks($intervalCount),
                BillingInterval::Monthly => now()->addMonths($intervalCount),
                BillingInterval::Yearly => now()->addYears($intervalCount),
                default => now()->addMonths($intervalCount),
            };

            $subscription->update([
                'status' => SubscriptionStatus::Active,
                'starts_at' => now(),
                'ends_at' => $newEndsAt,
            ]);

            event(new SubscriptionRenewedEvent($subscription));

            return $subscription->fresh();
        });
    }

    public function expire(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            $subscription->update([
                'status' => SubscriptionStatus::Expired,
                'ends_at' => now(),
            ]);

            event(new SubscriptionExpiredEvent($subscription));

            return $subscription->fresh();
        });
    }

    public function convertTrialToPaid(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            if (! $subscription->isOnTrial()) {
                throw new \Exception('Subscription is not on trial.');
            }

            $plan = $subscription->plan;
            $interval = $plan->interval;
            $intervalCount = $plan->interval_count;

            $endsAt = match ($interval) {
                BillingInterval::Daily => now()->addDays($intervalCount),
                BillingInterval::Weekly => now()->addWeeks($intervalCount),
                BillingInterval::Monthly => now()->addMonths($intervalCount),
                BillingInterval::Yearly => now()->addYears($intervalCount),
                default => now()->addMonths($intervalCount),
            };

            $subscription->update([
                'status' => SubscriptionStatus::Active,
                'trial_ends_at' => null,
                'starts_at' => now(),
                'ends_at' => $endsAt,
            ]);

            event(new TrialEndedEvent($subscription));
            event(new SubscriptionRenewedEvent($subscription));

            return $subscription->fresh();
        });
    }

    public function forceDelete(Subscription $subscription): bool
    {
        return DB::transaction(function () use ($subscription) {
            event(new SubscriptionDeletedEvent($subscription));

            return $subscription->forceDelete();
        });
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

    protected function determineChangeType(Plan $oldPlan, Plan $newPlan): PlanChangeType
    {
        $oldPrice = $oldPlan->price;
        $newPrice = $newPlan->price;

        if ($newPrice > $oldPrice) {
            return PlanChangeType::Upgrade;
        }

        if ($newPrice < $oldPrice) {
            return PlanChangeType::Downgrade;
        }

        return PlanChangeType::Switch;
    }

    protected function calculateProration(Subscription $subscription, Plan $oldPlan, Plan $newPlan): float
    {
        // Simple proration calculation based on remaining time
        // This can be customized based on business requirements
        if (! $subscription->ends_at) {
            return 0;
        }

        $totalDays = $subscription->starts_at->diffInDays($subscription->ends_at);
        $remainingDays = now()->diffInDays($subscription->ends_at);

        if ($totalDays <= 0 || $remainingDays <= 0) {
            return 0;
        }

        $oldDailyRate = $oldPlan->price / $totalDays;
        $newDailyRate = $newPlan->price / $totalDays;
        $remainingOldValue = $oldDailyRate * $remainingDays;
        $remainingNewValue = $newDailyRate * $remainingDays;

        return max(0, $remainingNewValue - $remainingOldValue);
    }

    protected function applyPlanChange(Subscription $subscription, Plan $newPlan, SubscriptionChange $change): void
    {
        $subscription->update([
            'plan_id' => $newPlan->id,
        ]);

        $change->update([
            'applied_at' => now(),
        ]);
    }
}
