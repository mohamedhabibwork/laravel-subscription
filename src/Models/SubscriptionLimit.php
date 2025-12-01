<?php

namespace HSubscription\LaravelSubscription\Models;

use HSubscription\LaravelSubscription\Enums\LimitType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SubscriptionLimit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'subscription_id',
        'feature_id',
        'custom_limit',
        'limit_type',
        'warning_threshold',
        'metadata',
    ];

    protected $casts = [
        'limit_type' => LimitType::class,
        'custom_limit' => 'integer',
        'warning_threshold' => 'integer',
        'metadata' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($limit) {
            if (empty($limit->uuid)) {
                $limit->uuid = (string) Str::uuid();
            }
        });
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }

    public function isHard(): bool
    {
        return $this->limit_type?->isHard() ?? false;
    }

    public function isSoft(): bool
    {
        return $this->limit_type?->isSoft() ?? false;
    }
}
