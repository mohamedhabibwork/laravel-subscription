# Changelog

All notable changes to `laravel-subscription` will be documented in this file.

## [1.0.0] - 2024-01-01

### Added

#### Core Features

- **Polymorphic Subscription System**: Any model can become a subscriber through morphable relationships
- **Subscription Plans**: Create plans with pricing, billing intervals (daily, weekly, monthly, yearly), trial periods, and grace periods
- **Feature Management**: Three feature types (boolean, limit, consumable) with granular access control
- **Module System**: Organize features into logical modules with parent-child hierarchy support
- **Usage Tracking & Metering**: Track feature consumption with automatic limit enforcement
- **Subscription Lifecycle Management**: Complete subscription management (create, cancel, resume, pause, renew, expire)
- **Plan Changes**: Upgrade, downgrade, or switch plans with proration calculation support
- **Trial Periods**: Automatic trial period handling with trial-to-paid conversion
- **Grace Periods**: Support for grace periods for failed payments

#### Database & Models

- **Plans Model**: Full CRUD operations with relationships to features and modules
- **Features Model**: Support for boolean, limit, and consumable feature types
- **Modules Model**: Module hierarchy with parent-child relationships
- **Subscriptions Model**: Polymorphic subscriber relationships with status tracking
- **SubscriptionUsage Model**: Track feature consumption with reset periods
- **SubscriptionLimit Model**: Custom limit overrides per subscription
- **ModuleActivation Model**: Track module activation status per subscription
- **SubscriptionChange Model**: Complete audit trail for plan changes
- **Soft Deletes**: All models support soft deletes for audit trails
- **UUID Support**: Secondary UUID identifiers for all models
- **BigInt Primary Keys**: Scalable database design

#### HasSubscriptions Trait

- `subscribe()`: Create new subscriptions
- `subscription()`: Get specific subscription by name
- `subscriptions()`: Get all subscriptions
- `activeSubscriptions()`: Get only active subscriptions
- `subscribedTo()`: Check if subscribed to plan
- `onTrial()`: Check trial status
- `cancelled()`: Check cancellation status
- `active()`: Check active status
- `hasFeature()`: Check feature access
- `hasModule()`: Check module access
- `canUseFeature()`: Check if can consume feature
- `consumeFeature()`: Record feature usage
- `remainingFeatureUsage()`: Get remaining usage
- `featureValue()`: Get feature limit/value
- `featureUsageHistory()`: Get historical usage
- `activeModules()`: Get all active modules
- `moduleFeatures()`: Get features in module
- `activateModule()`: Activate module
- `deactivateModule()`: Deactivate module
- `checkLimit()`: Validate against limit
- `setCustomLimit()`: Override plan limit
- `resetLimits()`: Reset all usage counters
- `limitStatus()`: Get limit and current usage

#### Services

- **SubscriptionService**: Complete subscription lifecycle management
  - Create subscriptions with trial periods
  - Plan changes with proration
  - Cancellation (immediate/end-of-period)
  - Resume, pause, unpause operations
  - Trial-to-paid conversion
  - Renewal handling
  - Expiration handling

- **FeatureService**: Feature access control and usage tracking
  - Feature access validation
  - Usage tracking and consumption
  - Limit enforcement
  - Usage reset based on billing cycles
  - Custom limit management

- **ModuleService**: Module management
  - Module activation/deactivation
  - Module access validation
  - Automatic module activation on subscription creation

#### Events

- `SubscriptionCreatedEvent`: Fired when subscription is created
- `SubscriptionCancelledEvent`: Fired when subscription is cancelled
- `SubscriptionResumedEvent`: Fired when subscription is resumed
- `SubscriptionExpiredEvent`: Fired when subscription expires
- `SubscriptionRenewedEvent`: Fired when subscription is renewed
- `SubscriptionDeletedEvent`: Fired when subscription is permanently deleted
- `PlanChangedEvent`: Fired when plan is changed
- `TrialEndingEvent`: Fired when trial is ending (configurable days before)
- `TrialEndedEvent`: Fired when trial ends
- `FeatureLimitReachedEvent`: Fired when feature limit is reached
- `FeatureLimitExceededEvent`: Fired when feature limit is exceeded
- `ModuleActivatedEvent`: Fired when module is activated
- `ModuleDeactivatedEvent`: Fired when module is deactivated
- `UsageRecordedEvent`: Fired when feature usage is recorded
- `LimitResetEvent`: Fired when limits are reset

#### Middleware & Gates

- `EnsureHasFeature` middleware: Protect routes based on feature access
- `EnsureHasModule` middleware: Protect routes based on module access
- `use-feature` gate: Check feature access
- `use-module` gate: Check module access
- `consume-feature` gate: Check if can consume feature

#### Configuration

- Default currency settings
- Default trial days
- Grace period configuration
- Proration behavior settings
- Usage reset schedules
- Overage policies (block, allow, allow_with_fee, notify)
- Feature inheritance on plan change
- Module inheritance on plan change
- Limit enforcement strictness
- Module activation rules
- Soft delete retention period
- UUID generation method
- Tax calculation settings
- Trial ending notification days

#### Testing

- Comprehensive test suite with >90% coverage
- Unit tests for all models
- Feature tests for services
- Integration tests for workflows
- Middleware tests
- Event tests
- Edge case tests

#### Documentation

- Complete README with usage examples
- API reference documentation
- Configuration reference
- Quick start guide
- Advanced usage examples

### New Features (v1.1.0)

#### Type Safety with Enums

The package now includes PHP 8.1 backed enums for type-safe comparisons:

- `SubscriptionStatus`: Active, Cancelled, Expired, OnTrial, PastDue, Paused
- `FeatureType`: Boolean, Limit, Consumable
- `ResetPeriod`: Never, Monthly, Yearly, Daily
- `BillingInterval`: Daily, Weekly, Monthly, Yearly
- `PlanChangeType`: Upgrade, Downgrade, Switch
- `LimitType`: Hard, Soft

All models, services, and tests now use enums instead of string literals for improved type safety and IDE support.

#### PHP 8 Attributes

New PHP 8.1 attributes for declarative access control:

- `RequiresFeature`: Declare feature requirements on controllers/methods
- `RequiresModule`: Declare module requirements on controllers/methods
- `RequiresSubscription`: Declare subscription requirements on controllers/methods
- `ConfigKey`: Map configuration keys to properties
- `ConfigDefault`: Set default values for configuration properties

#### Additional Middleware

New middleware classes for subscription status validation:

- `EnsureSubscriptionActive`: Ensures subscription is active
- `EnsureSubscriptionNotCancelled`: Ensures subscription is not cancelled
- `EnsureSubscriptionNotExpired`: Ensures subscription is not expired

All middleware support custom subscription names and provide clear error messages.

#### Configuration Helper

New `ConfigHelper` class provides:

- Type-safe configuration access methods
- Configuration validation on package boot
- Helper methods for common configuration values
- Validation for configuration values (proration behavior, overage policies, etc.)

#### Event Naming Standardization

All event classes now follow a consistent naming convention with the "Event" suffix:

- `SubscriptionCreatedEvent`
- `SubscriptionCancelledEvent`
- `PlanChangedEvent`
- `ModuleActivatedEvent`
- `ModuleDeactivatedEvent`
- And all other events...

This improves consistency and makes event classes easier to identify in the codebase.

### Technical Details

- Laravel 11+ support
- PHP 8.1+ support (enums and attributes require PHP 8.1+)
- Uses Spatie Laravel Package Tools
- Pest PHP for testing
- Laravel Pint for code formatting
- PHPStan for static analysis
