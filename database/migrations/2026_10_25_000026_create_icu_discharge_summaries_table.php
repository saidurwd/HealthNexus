<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('icu_discharge_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->constrained('icu_episodes')->cascadeOnDelete();
            $table->text('admission_reason_summary')->nullable();
            $table->text('diagnoses')->nullable();
            $table->text('major_events')->nullable();
            $table->text('procedures_summary')->nullable();
            $table->text('ventilation_summary')->nullable();
            $table->text('medications_summary')->nullable();
            $table->text('complications')->nullable();
            $table->text('laboratory_course')->nullable();
            $table->text('imaging_summary')->nullable();
            $table->text('current_status')->nullable();
            $table->string('destination')->nullable();
            $table->text('follow_up_plan')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->index(['episode_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icu_discharge_summaries');
    }
};
