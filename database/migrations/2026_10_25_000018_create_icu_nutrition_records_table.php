<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('icu_nutrition_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->constrained('icu_episodes')->cascadeOnDelete();
            $table->string('route');
            $table->string('feeding_type')->nullable();
            $table->decimal('rate', 8, 2)->nullable();
            $table->string('rate_unit')->nullable();
            $table->text('plan')->nullable();
            $table->boolean('dietitian_reviewed')->default(false);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['episode_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icu_nutrition_records');
    }
};
