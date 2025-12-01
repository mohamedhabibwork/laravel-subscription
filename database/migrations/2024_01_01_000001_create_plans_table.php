<?php

use HSubscription\LaravelSubscription\Enums\BillingInterval;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('interval')->default(BillingInterval::Monthly); // monthly|yearly|weekly
            $table->integer('interval_count')->default(1);
            $table->integer('trial_days')->default(0);
            $table->integer('grace_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('tier')->nullable(); // personal|business|enterprise
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('uuid');
            $table->index('slug');
            $table->index('is_active');
            $table->index('tier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
