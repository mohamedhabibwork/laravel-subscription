<?php

namespace HSubscription\LaravelSubscription\Models;

use HSubscription\LaravelSubscription\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'subscribable_type',
        'subscribable_id',
        'plan_id',
        'name',
        'status',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'cancelled_at',
        'paused_at',
        'resumed_at',
        'metadata',
    ];

    protected $casts = [
        'status' => SubscriptionStatus::class,
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'paused_at' => 'datetime',
        'resumed_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($subscription) {
            if (empty($subscription->uuid)) {
                $subscription->uuid = (string) Str::uuid();
            }
        });
    }

    public function subscribable(): MorphTo
    {
        return $this->morphTo();
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function usage(): HasMany
    {
        return $this->hasMany(SubscriptionUsage::class);
    }

    public function limits(): HasMany
    {
        return $this->hasMany(SubscriptionLimit::class);
    }

    public function moduleActivations(): HasMany
    {
        return $this->hasMany(ModuleActivation::class);
    }

    public function changes(): HasMany
    {
        return $this->hasMany(SubscriptionChange::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', SubscriptionStatus::Active->value);
    }

    public function scopeOnTrial($query)
    {
        return $query->where('status', SubscriptionStatus::OnTrial->value);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', SubscriptionStatus::Cancelled->value);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', SubscriptionStatus::Expired->value);
    }

    public function scopePastDue($query)
    {
        return $query->where('status', SubscriptionStatus::PastDue->value);
    }

    public function scopePaused($query)
    {
        return $query->where('status', SubscriptionStatus::Paused->value);
    }

    public function isActive(): bool
    {
        return $this->status?->isActive() ?? false;
    }

    public function isOnTrial(): bool
    {
        return $this->status?->isOnTrial() ?? false;
    }

    public function isCancelled(): bool
    {
        return $this->status?->isCancelled() ?? false;
    }

    public function isExpired(): bool
    {
        return $this->status?->isExpired() ?? false;
    }

    public function isPastDue(): bool
    {
        return $this->status?->isPastDue() ?? false;
    }

    public function isPaused(): bool
    {
        return $this->status?->isPaused() ?? false;
    }
}
