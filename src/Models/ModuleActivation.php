<?php

namespace HSubscription\LaravelSubscription\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ModuleActivation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'subscription_id',
        'module_id',
        'is_active',
        'activated_at',
        'deactivated_at',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'activated_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($activation) {
            if (empty($activation->uuid)) {
                $activation->uuid = (string) Str::uuid();
            }
        });
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
