<?php

namespace HSubscription\LaravelSubscription\Models;

use HSubscription\LaravelSubscription\Enums\PlanChangeType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SubscriptionChange extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'subscription_id',
        'from_plan_id',
        'to_plan_id',
        'change_type',
        'is_immediate',
        'scheduled_for',
        'applied_at',
        'proration_amount',
        'metadata',
    ];

    protected $casts = [
        'change_type' => PlanChangeType::class,
        'is_immediate' => 'boolean',
        'scheduled_for' => 'datetime',
        'applied_at' => 'datetime',
        'proration_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($change) {
            if (empty($change->uuid)) {
                $change->uuid = (string) Str::uuid();
            }
        });
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function fromPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'from_plan_id');
    }

    public function toPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'to_plan_id');
    }

    public function scopeImmediate($query)
    {
        return $query->where('is_immediate', true);
    }

    public function scopeScheduled($query)
    {
        return $query->where('is_immediate', false)
            ->whereNotNull('scheduled_for');
    }

    public function scopeApplied($query)
    {
        return $query->whereNotNull('applied_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('applied_at');
    }

    public function isUpgrade(): bool
    {
        return $this->change_type?->isUpgrade() ?? false;
    }

    public function isDowngrade(): bool
    {
        return $this->change_type?->isDowngrade() ?? false;
    }

    public function isSwitch(): bool
    {
        return $this->change_type?->isSwitch() ?? false;
    }
}
