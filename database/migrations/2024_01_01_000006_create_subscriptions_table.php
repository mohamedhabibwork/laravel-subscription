<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->morphs('subscribable'); // polymorphic
            $table->foreignId('plan_id')->constrained('plans')->onDelete('restrict');
            $table->string('name')->default('default'); // default|main|secondary
            $table->string('status')->default('on_trial'); // active|cancelled|expired|on_trial|past_due|paused
            $table->timestampTz('trial_ends_at')->nullable();
            $table->timestampTz('starts_at')->nullable();
            $table->timestampTz('ends_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampTz('paused_at')->nullable();
            $table->timestampTz('resumed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('uuid');
            $table->index('plan_id');
            $table->index('status');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
