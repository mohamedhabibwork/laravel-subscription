<?php

namespace HSubscription\LaravelSubscription\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PlanFeature extends Pivot
{
    use SoftDeletes;

    protected $table = 'plan_feature';

    protected $fillable = [
        'uuid',
        'plan_id',
        'feature_id',
        'value',
        'metadata',
    ];

    protected $casts = [
        'value' => 'integer',
        'metadata' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($planFeature) {
            if (empty($planFeature->uuid)) {
                $planFeature->uuid = (string) Str::uuid();
            }
        });
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }
}
