<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursing_care_plan_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('care_plan_id')->constrained('nursing_care_plans')->cascadeOnDelete();
            $table->foreignId('nursing_diagnosis_id')->nullable()->constrained('nursing_diagnoses')->nullOnDelete();
            $table->text('goal_text');
            $table->date('target_date')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index(['care_plan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_care_plan_goals');
    }
};
