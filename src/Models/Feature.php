<?php

namespace HSubscription\LaravelSubscription\Models;

use HSubscription\LaravelSubscription\Enums\FeatureType;
use HSubscription\LaravelSubscription\Enums\ResetPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Feature extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'module_id',
        'name',
        'slug',
        'description',
        'type',
        'default_value',
        'reset_period',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'type' => FeatureType::class,
        'reset_period' => ResetPeriod::class,
        'default_value' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($feature) {
            if (empty($feature->uuid)) {
                $feature->uuid = (string) Str::uuid();
            }
        });
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'plan_feature')
            ->using(PlanFeature::class)
            ->withPivot('value', 'metadata')
            ->withTimestamps()
            ->whereNull('plan_feature.deleted_at');
    }

    public function subscriptionUsage(): HasMany
    {
        return $this->hasMany(SubscriptionUsage::class);
    }

    public function subscriptionLimits(): HasMany
    {
        return $this->hasMany(SubscriptionLimit::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, FeatureType|string $type)
    {
        if ($type instanceof FeatureType) {
            return $query->where('type', $type);
        }

        return $query->where('type', $type);
    }

    public function isBoolean(): bool
    {
        return $this->type?->isBoolean() ?? false;
    }

    public function isLimit(): bool
    {
        return $this->type?->isLimit() ?? false;
    }

    public function isConsumable(): bool
    {
        return $this->type?->isConsumable() ?? false;
    }
}
