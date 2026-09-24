<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursing_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('episode_id')->constrained('nursing_episodes')->cascadeOnDelete();
            $table->foreignId('admission_id')->constrained('ipd_admissions')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ward_id')->nullable()->constrained('ipd_wards')->nullOnDelete();
            $table->foreignId('bed_id')->nullable()->constrained('ipd_beds')->nullOnDelete();
            $table->foreignId('nurse_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('nursing_shifts')->nullOnDelete();
            $table->string('assignment_type')->default('patient');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['episode_id', 'ended_at']);
            $table->index(['nurse_id', 'ended_at']);
            $table->index(['admission_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_assignments');
    }
};
