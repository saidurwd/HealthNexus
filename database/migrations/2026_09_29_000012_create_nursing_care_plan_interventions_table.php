<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursing_care_plan_interventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('care_plan_id')->constrained('nursing_care_plans')->cascadeOnDelete();
            $table->foreignId('goal_id')->nullable()->constrained('nursing_care_plan_goals')->nullOnDelete();
            $table->string('intervention_type');
            $table->string('frequency')->nullable();
            $table->foreignId('responsible_nurse_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('active');
            $table->text('evaluation_notes')->nullable();
            $table->timestamps();

            $table->index(['care_plan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_care_plan_interventions');
    }
};
