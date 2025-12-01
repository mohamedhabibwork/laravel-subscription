<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency code for subscription plans. This should be a
    | three-letter ISO 4217 currency code (e.g., USD, EUR, GBP).
    |
    */

    'default_currency' => env('SUBSCRIPTION_DEFAULT_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Default Trial Days
    |--------------------------------------------------------------------------
    |
    | The default number of trial days for new subscriptions when not
    | explicitly specified in the plan or subscription options.
    |
    */

    'default_trial_days' => env('SUBSCRIPTION_DEFAULT_TRIAL_DAYS', 0),

    /*
    |--------------------------------------------------------------------------
    | Grace Period Days
    |--------------------------------------------------------------------------
    |
    | The default number of grace period days for failed payments before
    | a subscription is marked as past_due or cancelled.
    |
    */

    'default_grace_days' => env('SUBSCRIPTION_DEFAULT_GRACE_DAYS', 0),

    /*
    |--------------------------------------------------------------------------
    | Proration Behavior
    |--------------------------------------------------------------------------
    |
    | Controls how proration is calculated when changing plans mid-cycle.
    | Options: 'time_based', 'immediate', 'none'
    |
    */

    'proration_behavior' => env('SUBSCRIPTION_PRORATION_BEHAVIOR', 'time_based'),

    /*
    |--------------------------------------------------------------------------
    | Usage Reset Schedule
    |--------------------------------------------------------------------------
    |
    | Cron expression for when usage should be reset. This is used for
    | features with reset_period set to 'monthly', 'yearly', or 'daily'.
    | Default: daily at midnight
    |
    */

    'usage_reset_schedule' => env('SUBSCRIPTION_USAGE_RESET_SCHEDULE', '0 0 * * *'),

    /*
    |--------------------------------------------------------------------------
    | Overage Policies
    |--------------------------------------------------------------------------
    |
    | Defines how the system handles usage that exceeds feature limits.
    | Options: 'block', 'allow', 'allow_with_fee', 'notify'
    |
    */

    'overage_policy' => env('SUBSCRIPTION_OVERAGE_POLICY', 'block'),

    /*
    |--------------------------------------------------------------------------
    | Overage Fee Multiplier
    |--------------------------------------------------------------------------
    |
    | If overage_policy is set to 'allow_with_fee', this multiplier is
    | applied to calculate the overage fee. For example, 1.5 means 150%
    | of the base feature price per unit over the limit.
    |
    */

    'overage_fee_multiplier' => env('SUBSCRIPTION_OVERAGE_FEE_MULTIPLIER', 1.5),

    /*
    |--------------------------------------------------------------------------
    | Feature Inheritance on Plan Change
    |--------------------------------------------------------------------------
    |
    | When changing plans, this determines if features from the old plan
    | should be inherited if they exist in the new plan. If false, all
    | features are reset based on the new plan.
    |
    */

    'feature_inheritance_on_plan_change' => env('SUBSCRIPTION_FEATURE_INHERITANCE', true),

    /*
    |--------------------------------------------------------------------------
    | Module Inheritance on Plan Change
    |--------------------------------------------------------------------------
    |
    | When changing plans, this determines if module activations from the
    | old plan should be inherited if they exist in the new plan.
    |
    */

    'module_inheritance_on_plan_change' => env('SUBSCRIPTION_MODULE_INHERITANCE', true),

    /*
    |--------------------------------------------------------------------------
    | Default Feature Values
    |--------------------------------------------------------------------------
    |
    | Default values for features when not explicitly set in the plan.
    | This applies to limit and consumable type features.
    |
    */

    'default_feature_value' => env('SUBSCRIPTION_DEFAULT_FEATURE_VALUE', 0),

    /*
    |--------------------------------------------------------------------------
    | Limit Enforcement Strictness
    |--------------------------------------------------------------------------
    |
    | Controls how strictly limits are enforced. Options:
    | 'strict' - Blocks all usage exceeding limits
    | 'soft' - Allows usage but logs warnings
    | 'flexible' - Allows usage with notifications
    |
    */

    'limit_enforcement' => env('SUBSCRIPTION_LIMIT_ENFORCEMENT', 'strict'),

    /*
    |--------------------------------------------------------------------------
    | Module Activation Rules
    |--------------------------------------------------------------------------
    |
    | Rules for automatic module activation when a subscription is created
    | or plan is changed. Options: 'auto', 'manual', 'plan_based'
    |
    */

    'module_activation_rule' => env('SUBSCRIPTION_MODULE_ACTIVATION_RULE', 'plan_based'),

    /*
    |--------------------------------------------------------------------------
    | Soft Delete Retention Period
    |--------------------------------------------------------------------------
    |
    | Number of days to retain soft-deleted records before permanent deletion.
    | Set to null to never auto-delete soft-deleted records.
    |
    */

    'soft_delete_retention_days' => env('SUBSCRIPTION_SOFT_DELETE_RETENTION_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | UUID Generation Method
    |--------------------------------------------------------------------------
    |
    | Method for generating UUIDs. Options: 'uuid', 'uuid4', 'ordered'
    | 'uuid' and 'uuid4' use Laravel's Str::uuid()
    | 'ordered' uses time-based UUIDs (requires additional package)
    |
    */

    'uuid_generation' => env('SUBSCRIPTION_UUID_GENERATION', 'uuid'),

    /*
    |--------------------------------------------------------------------------
    | Date Format Preferences
    |--------------------------------------------------------------------------
    |
    | Default date format for displaying subscription dates in the system.
    |
    */

    'date_format' => env('SUBSCRIPTION_DATE_FORMAT', 'Y-m-d H:i:s'),

    /*
    |--------------------------------------------------------------------------
    | Tax Calculation Settings
    |--------------------------------------------------------------------------
    |
    | Enable or disable tax calculation for subscription pricing.
    | If enabled, tax rates should be configured per plan or globally.
    |
    */

    'enable_tax_calculation' => env('SUBSCRIPTION_ENABLE_TAX', false),

    'default_tax_rate' => env('SUBSCRIPTION_DEFAULT_TAX_RATE', 0),

    /*
    |--------------------------------------------------------------------------
    | Trial Ending Notification
    |--------------------------------------------------------------------------
    |
    | Number of days before trial ends to send notification. Set to null
    | to disable trial ending notifications.
    |
    */

    'trial_ending_notification_days' => env('SUBSCRIPTION_TRIAL_ENDING_NOTIFICATION_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Subscription Model
    |--------------------------------------------------------------------------
    |
    | The subscription model class. You can override this if you need to
    | extend the default Subscription model.
    |
    */

    'subscription_model' => \HSubscription\LaravelSubscription\Models\Subscription::class,

    /*
    |--------------------------------------------------------------------------
    | Plan Model
    |--------------------------------------------------------------------------
    |
    | The plan model class. You can override this if you need to extend
    | the default Plan model.
    |
    */

    'plan_model' => \HSubscription\LaravelSubscription\Models\Plan::class,

    /*
    |--------------------------------------------------------------------------
    | Feature Model
    |--------------------------------------------------------------------------
    |
    | The feature model class. You can override this if you need to extend
    | the default Feature model.
    |
    */

    'feature_model' => \HSubscription\LaravelSubscription\Models\Feature::class,

    /*
    |--------------------------------------------------------------------------
    | Module Model
    |--------------------------------------------------------------------------
    |
    | The module model class. You can override this if you need to extend
    | the default Module model.
    |
    */

    'module_model' => \HSubscription\LaravelSubscription\Models\Module::class,

];
