<?php

namespace HSubscription\LaravelSubscription\Models;

use HSubscription\LaravelSubscription\Enums\BillingInterval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'interval',
        'interval_count',
        'trial_days',
        'grace_days',
        'is_active',
        'tier',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'interval' => BillingInterval::class,
        'trial_days' => 'integer',
        'grace_days' => 'integer',
        'interval_count' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($plan) {
            if (empty($plan->uuid)) {
                $plan->uuid = (string) Str::uuid();
            }
        });
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'plan_feature')
            ->using(PlanFeature::class)
            ->withPivot('value', 'metadata')
            ->withTimestamps()
            ->whereNull('plan_feature.deleted_at');
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'plan_module')
            ->using(PlanModule::class)
            ->withPivot('is_enabled', 'metadata')
            ->withTimestamps()
            ->whereNull('plan_module.deleted_at');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function subscriptionChanges(): HasMany
    {
        return $this->hasMany(SubscriptionChange::class, 'to_plan_id');
    }

    public function fromSubscriptionChanges(): HasMany
    {
        return $this->hasMany(SubscriptionChange::class, 'from_plan_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByTier($query, string $tier)
    {
        return $query->where('tier', $tier);
    }
}
