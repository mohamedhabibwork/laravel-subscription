<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_changes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('subscription_id')->constrained('subscriptions')->onDelete('cascade');
            $table->foreignId('from_plan_id')->nullable()->constrained('plans')->onDelete('set null');
            $table->foreignId('to_plan_id')->constrained('plans')->onDelete('restrict');
            $table->string('change_type'); // upgrade|downgrade|switch
            $table->boolean('is_immediate')->default(true);
            $table->timestampTz('scheduled_for')->nullable();
            $table->timestampTz('applied_at')->nullable();
            $table->decimal('proration_amount', 10, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('uuid');
            $table->index('subscription_id');
            $table->index('from_plan_id');
            $table->index('to_plan_id');
            $table->index('change_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_changes');
    }
};
