# Product Requirements Document: Laravel Subscription Package

## 1. Product Overview

### 1.1 Purpose
A flexible Laravel package that enables subscription management for any subscribable entity with support for multiple plans, features, modules, metering, usage limits, and polymorphic relationships.

### 1.2 Target Users
- SaaS application developers
- Multi-tenant platform builders
- Marketplace creators
- Enterprise application developers

### 1.3 Key Objectives
- Provide flexible subscription management using polymorphic relationships
- Support multiple subscription models for any entity type
- Enable feature and module-based access control
- Track usage and implement metering with limits
- Handle billing cycles and renewals
- Support plan upgrades, downgrades, and cancellations
- Maintain complete audit trails with soft deletes

## 2. Core Features

### 2.1 Polymorphic Subscriber System
**Description**: Any model can be a subscriber through morphable relationships

**Requirements**:
- Subscriber entities use polymorphic type and ID
- Single subscriber can have multiple active subscriptions
- Each subscription belongs to one subscriber
- Maintain subscriber history across all subscription changes
- Soft delete support for historical data retention

### 2.2 Subscription Plans
**Description**: Define various subscription tiers with different features, modules, and pricing

**Requirements**:
- Plan name, description, and slug
- Pricing (amount, currency, billing interval)
- Trial period configuration (days)
- Grace period for failed payments
- Active/inactive status
- Plan metadata for custom attributes
- Plan grouping (personal, business, enterprise)
- Soft delete support to maintain historical plan data
- UUID secondary identifier for distributed systems
- BigInt primary key for scalability

### 2.3 Features Management
**Description**: Granular feature control tied to plans

**Requirements**:
- Feature name, slug, and description
- Feature types: boolean, limit, consumable
- Boolean features: simple on/off access
- Limit features: maximum allowed usage (e.g., 100 projects)
- Consumable features: depleting resources (e.g., API calls, credits)
- Feature can be assigned to multiple plans
- Custom feature values per plan override
- Feature reset policies (monthly, annually, never)
- Soft delete support
- UUID secondary identifier
- BigInt primary key

### 2.4 Modules Management
**Description**: Organize features into logical modules for better organization

**Requirements**:
- Module name, slug, and description
- Module can contain multiple features
- Modules can be enabled/disabled per plan
- Module hierarchy support (parent-child relationships)
- Module activation tracking per subscription
- Soft delete support
- UUID secondary identifier
- BigInt primary key

### 2.5 Subscription Management
**Description**: Complete subscription lifecycle handling

**Requirements**:
- Create new subscriptions with trial periods
- Automatic trial-to-paid conversion
- Subscription renewal tracking
- Cancellation (immediate or end of period)
- Reactivation of cancelled subscriptions
- Subscription pausing/resumption
- Grace periods for payment failures
- Subscription status tracking (active, cancelled, expired, on_trial, past_due, paused)
- Soft delete for audit trails
- UUID secondary identifier
- BigInt primary key

### 2.6 Plan Changes
**Description**: Handle upgrades, downgrades, and plan switches

**Requirements**:
- Immediate plan change with prorated billing
- Scheduled plan change at period end
- Upgrade/downgrade logic with feature comparison
- Handle feature access during transitions
- Proration calculation for mid-cycle changes
- Change history tracking with soft deletes
- UUID and BigInt support

### 2.7 Usage Tracking & Metering
**Description**: Monitor feature consumption and enforce limits

**Requirements**:
- Track usage for consumable features
- Enforce limits on limited features
- Usage reset based on billing cycle
- Usage reports per subscription
- Overage tracking and reporting
- Usage webhooks for monitoring
- Historical usage data with soft deletes
- UUID secondary identifier
- BigInt primary key

### 2.8 Limits & Quotas
**Description**: Enforce usage boundaries per feature and module

**Requirements**:
- Define hard limits per feature
- Define soft limits with warnings
- Quota management per subscription
- Limit reset schedules
- Overage policies (block/allow/notify)
- Limit inheritance from plans
- Custom limit overrides per subscription

### 2.9 Feature Access Control
**Description**: Runtime feature checking and enforcement

**Requirements**:
- Check if subscriber has access to a feature
- Check if subscriber has access to a module
- Validate feature limits before actions
- Consume feature usage programmatically
- Check remaining feature allowance
- Feature access middleware for routes
- Gate integration for authorization
- Module-level access control

## 3. Technical Architecture

### 3.1 Polymorphic Relationships
**Subscriber Morph Relations**:
- One subscriber (any entity) can have many subscriptions
- One subscription belongs to one subscribable entity

**Feature Usage Morph Relations**:
- Usage tracking linked to any subscriber type
- Usage records tied to specific features

### 3.2 Data Models

**Subscribers (Polymorphic)**:
- Uses trait: HasSubscriptions
- Morphable through subscribable_type and subscribable_id

**Plans**:
- BigInt primary key
- UUID secondary identifier
- Standalone model with plan details
- Has many features through pivot
- Has many modules through pivot
- Soft deletes enabled

**Features**:
- BigInt primary key
- UUID secondary identifier
- Standalone model with feature configuration
- Belongs to many plans with pivot values
- Belongs to many modules
- Soft deletes enabled

**Modules**:
- BigInt primary key
- UUID secondary identifier
- Standalone model for feature grouping
- Has many features
- Belongs to many plans
- Self-referential for hierarchy
- Soft deletes enabled

**Subscriptions**:
- BigInt primary key
- UUID secondary identifier
- Belongs to subscriber (polymorphic)
- Belongs to plan
- Tracks status and dates
- Soft deletes enabled

**Feature Usage**:
- BigInt primary key
- UUID secondary identifier
- Belongs to subscription
- Belongs to feature
- Tracks consumption
- Soft deletes enabled

**Module Activations**:
- BigInt primary key
- UUID secondary identifier
- Tracks which modules are active per subscription
- Activation timestamps
- Soft deletes enabled

**Plan Changes**:
- BigInt primary key
- UUID secondary identifier
- Audit trail for all plan modifications
- Soft deletes enabled

### 3.3 Database Considerations
- Soft deletes on all models for audit trails
- Timestamps on all models (created_at, updated_at, deleted_at)
- UUID support for distributed systems (secondary key)
- BigInt primary keys for scalability
- JSON columns for metadata
- Indexes on polymorphic columns
- Indexes on UUID columns
- Foreign key constraints with cascade options
- Composite indexes for performance

## 4. User Workflows

### 4.1 New Subscription Flow
1. Subscriber selects a plan
2. System creates subscription with trial (if applicable)
3. Trial period begins, features and modules activated
4. Trial ends, first payment processed
5. Subscription becomes active

### 4.2 Plan Upgrade Flow
1. Subscriber requests plan upgrade
2. System calculates prorated amount
3. Immediate upgrade applied
4. Feature and module limits increased
5. New billing cycle starts or prorated charge

### 4.3 Cancellation Flow
1. Subscriber requests cancellation
2. Choose immediate or end-of-period
3. System soft deletes subscription (marks cancelled)
4. Features remain active until end date (if scheduled)
5. Subscription expires and features revoked

### 4.4 Usage Tracking Flow
1. Application consumes feature
2. System records usage increment
3. Check against limit if applicable
4. Throw exception if limit exceeded
5. Reset usage at cycle boundaries
6. Maintain historical usage data

### 4.5 Module Activation Flow
1. Plan includes specific modules
2. Subscription activates plan modules
3. Module features become available
4. Track module activation status
5. Deactivate on plan change if not included

## 5. API Requirements

### 5.1 Subscription Methods
- `subscribe($plan, $options)` - Create new subscription
- `subscription($name)` - Get specific subscription
- `subscriptions()` - Get all subscriptions (with trashed)
- `activeSubscriptions()` - Get only active subscriptions
- `subscribedTo($plan)` - Check if subscribed to plan
- `onTrial()` - Check trial status
- `cancelled()` - Check cancellation status
- `active()` - Check active status

### 5.2 Plan Management Methods
- `changePlan($newPlan)` - Switch to different plan
- `cancel($immediately)` - Cancel subscription (soft delete)
- `resume()` - Resume cancelled subscription
- `pause()` - Pause subscription
- `unpause()` - Resume paused subscription
- `forceDelete()` - Permanently delete subscription

### 5.3 Feature Access Methods
- `hasFeature($feature)` - Check feature access
- `hasModule($module)` - Check module access
- `canUseFeature($feature, $amount)` - Check if can consume
- `consumeFeature($feature, $amount)` - Record usage
- `remainingFeatureUsage($feature)` - Get remaining allowance
- `featureValue($feature)` - Get feature limit/value
- `featureUsageHistory($feature)` - Get historical usage

### 5.4 Module Methods
- `activeModules()` - Get all active modules
- `moduleFeatures($module)` - Get features in module
- `activateModule($module)` - Enable module for subscription
- `deactivateModule($module)` - Disable module

### 5.5 Limit Methods
- `checkLimit($feature)` - Validate against limit
- `setCustomLimit($feature, $value)` - Override plan limit
- `resetLimits()` - Reset all usage counters
- `limitStatus($feature)` - Get limit and current usage

## 6. Events & Notifications

### 6.1 Events
- SubscriptionCreated
- SubscriptionCancelled (soft delete)
- SubscriptionResumed
- SubscriptionExpired
- SubscriptionRenewed
- SubscriptionDeleted (force delete)
- PlanChanged
- TrialEnding (7 days before)
- TrialEnded
- FeatureLimitReached
- FeatureLimitExceeded
- ModuleActivated
- ModuleDeactivated
- UsageRecorded
- LimitReset

### 6.2 Notifications
- Trial expiration reminder
- Subscription renewal confirmation
- Plan change confirmation
- Feature limit warnings (soft and hard limits)
- Module activation confirmation
- Usage threshold notifications

## 7. Configuration Options

### 7.1 Package Config
- Default currency
- Default trial days
- Grace period days
- Proration behavior
- Date format preferences
- Tax calculation settings
- Soft delete retention period
- UUID generation method

### 7.2 Feature Behaviors
- Usage reset schedule (cron expression)
- Overage policies (block/allow with fee)
- Feature inheritance on plan change
- Default feature values
- Limit enforcement strictness

### 7.3 Module Behaviors
- Module activation rules
- Module dependency management
- Module inheritance on plan change
- Default module states

## 8. Security & Permissions

### 8.1 Access Control
- Ensure subscriber owns subscription before modifications
- Validate plan changes are allowed
- Protect sensitive subscription data
- Middleware for feature-based route protection
- Middleware for module-based route protection

### 8.2 Data Privacy
- Soft delete for audit trails
- Anonymize cancelled subscriber data (optional)
- GDPR-compliant data export
- Historical data retention policies

## 9. Testing Requirements

### 9.1 Unit Tests
- Subscription creation and lifecycle
- Plan changes and proration
- Feature access validation
- Module access validation
- Usage tracking accuracy
- Limit enforcement
- Polymorphic relationships
- Soft delete functionality
- UUID generation

### 9.2 Integration Tests
- End-to-end subscription flows
- Event dispatching
- Database transactions
- Soft delete recovery

### 9.3 Feature Tests
- Trial period handling
- Grace period behavior
- Feature limit enforcement
- Module activation/deactivation
- Subscription status transitions
- Historical data queries

## 10. Documentation Requirements

### 10.1 Installation Guide
- Package installation via Composer
- Migration publishing and execution
- Configuration file publishing
- Service provider registration

### 10.2 Usage Documentation
- Quick start guide
- Subscriber setup (adding trait)
- Plan, feature, and module creation
- Subscription management examples
- Feature and module checking examples
- Usage tracking examples
- Limit management examples
- Event listener setup
- Soft delete handling

### 10.3 API Reference
- Complete method documentation
- Parameter descriptions
- Return value specifications
- Exception handling

## 11. Performance Considerations

### 11.1 Optimization
- Eager loading for polymorphic relationships
- Caching for frequently accessed plans/features/modules
- Database query optimization
- Index strategy for large datasets
- Efficient soft delete queries

### 11.2 Scalability
- BigInt primary keys for unlimited records
- UUID support for distributed systems
- Support for millions of subscriptions
- Efficient usage tracking
- Background job processing for renewals
- Queue handling for notifications

## 12. Future Enhancements

### 12.1 Phase 2 Features
- Multi-currency support with conversion
- Dunning management (automatic retry)
- Affiliate/referral tracking
- Coupon and discount system
- Add-ons and one-time purchases
- Invoice and payment tracking integration
- Module marketplace

### 12.2 Phase 3 Features
- Payment gateway integrations (Stripe, Paddle, PayPal)
- Automatic invoice generation (PDF)
- Revenue analytics dashboard
- Subscription forecasting
- Customer portal for self-service
- Advanced module dependencies
- Feature flagging system

## 13. Success Metrics

### 13.1 Technical Metrics
- Package installation count
- API response times < 100ms
- Zero data loss on plan changes
- 99.9% uptime for subscription checks
- Soft delete recovery rate
- Query performance with large datasets

### 13.2 Business Metrics
- Developer adoption rate
- Time to implement (target < 1 hour)
- Subscription conversion rate improvement
- Churn reduction through better UX
- Feature utilization rates
- Module adoption rates