<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_usage', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('subscription_id')->constrained('subscriptions')->onDelete('cascade');
            $table->foreignId('feature_id')->constrained('features')->onDelete('cascade');
            $table->integer('used')->default(0);
            $table->integer('limit')->nullable(); // snapshot of limit at time
            $table->timestampTz('valid_until')->nullable();
            $table->timestampTz('reset_at')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('uuid');
            $table->unique(['subscription_id', 'feature_id', 'valid_until']);
            $table->index('subscription_id');
            $table->index('feature_id');
            $table->index('valid_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_usage');
    }
};
