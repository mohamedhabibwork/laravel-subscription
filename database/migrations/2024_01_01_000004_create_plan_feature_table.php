<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_feature', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade');
            $table->foreignId('feature_id')->constrained('features')->onDelete('cascade');
            $table->integer('value')->nullable(); // override default_value
            $table->json('metadata')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('uuid');
            $table->unique(['plan_id', 'feature_id']);
            $table->index('plan_id');
            $table->index('feature_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_feature');
    }
};
