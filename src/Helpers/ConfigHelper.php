<?php

namespace HSubscription\LaravelSubscription\Helpers;

class ConfigHelper
{
    public static function currency(): string
    {
        return config('subscription.default_currency', 'USD');
    }

    public static function trialDays(): int
    {
        return (int) config('subscription.default_trial_days', 0);
    }

    public static function graceDays(): int
    {
        return (int) config('subscription.default_grace_days', 0);
    }

    public static function prorationBehavior(): string
    {
        return config('subscription.proration_behavior', 'time_based');
    }

    public static function overagePolicy(): string
    {
        return config('subscription.overage_policy', 'block');
    }

    public static function overageFeeMultiplier(): float
    {
        return (float) config('subscription.overage_fee_multiplier', 1.5);
    }

    public static function featureInheritance(): bool
    {
        return (bool) config('subscription.feature_inheritance_on_plan_change', true);
    }

    public static function moduleInheritance(): bool
    {
        return (bool) config('subscription.module_inheritance_on_plan_change', true);
    }

    public static function limitEnforcement(): string
    {
        return config('subscription.limit_enforcement', 'strict');
    }

    public static function moduleActivationRule(): string
    {
        return config('subscription.module_activation_rule', 'plan_based');
    }

    public static function trialEndingNotificationDays(): ?int
    {
        $days = config('subscription.trial_ending_notification_days', 7);

        return $days === null ? null : (int) $days;
    }

    public static function validate(): array
    {
        $errors = [];

        $validProrationBehaviors = ['time_based', 'immediate', 'none'];
        if (! in_array(self::prorationBehavior(), $validProrationBehaviors)) {
            $errors[] = 'Invalid proration_behavior. Must be one of: '.implode(', ', $validProrationBehaviors);
        }

        $validOveragePolicies = ['block', 'allow', 'allow_with_fee', 'notify'];
        if (! in_array(self::overagePolicy(), $validOveragePolicies)) {
            $errors[] = 'Invalid overage_policy. Must be one of: '.implode(', ', $validOveragePolicies);
        }

        $validLimitEnforcements = ['strict', 'soft', 'flexible'];
        if (! in_array(self::limitEnforcement(), $validLimitEnforcements)) {
            $errors[] = 'Invalid limit_enforcement. Must be one of: '.implode(', ', $validLimitEnforcements);
        }

        $validModuleActivationRules = ['auto', 'manual', 'plan_based'];
        if (! in_array(self::moduleActivationRule(), $validModuleActivationRules)) {
            $errors[] = 'Invalid module_activation_rule. Must be one of: '.implode(', ', $validModuleActivationRules);
        }

        if (self::trialDays() < 0) {
            $errors[] = 'default_trial_days must be >= 0';
        }

        if (self::graceDays() < 0) {
            $errors[] = 'default_grace_days must be >= 0';
        }

        if (self::overageFeeMultiplier() < 0) {
            $errors[] = 'overage_fee_multiplier must be >= 0';
        }

        return $errors;
    }
}
