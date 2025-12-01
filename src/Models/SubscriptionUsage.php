<?php

namespace HSubscription\LaravelSubscription\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SubscriptionUsage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'subscription_usage';

    protected $fillable = [
        'uuid',
        'subscription_id',
        'feature_id',
        'used',
        'limit',
        'valid_until',
        'reset_at',
    ];

    protected $casts = [
        'used' => 'integer',
        'limit' => 'integer',
        'valid_until' => 'datetime',
        'reset_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($usage) {
            if (empty($usage->uuid)) {
                $usage->uuid = (string) Str::uuid();
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

    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('valid_until')
                ->orWhere('valid_until', '>', now());
        });
    }

    public function scopeExpired($query)
    {
        return $query->where('valid_until', '<=', now());
    }
}
