<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vital_signs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('recorded_at')->nullable();
            $table->decimal('temperature', 5, 2)->nullable();
            $table->string('temperature_unit', 10)->default('celsius');
            $table->integer('systolic')->nullable();
            $table->integer('diastolic')->nullable();
            $table->string('bp_unit', 10)->default('mmhg');
            $table->integer('pulse_rate')->nullable();
            $table->integer('respiratory_rate')->nullable();
            $table->decimal('height', 5, 2)->nullable();
            $table->decimal('weight', 6, 2)->nullable();
            $table->decimal('bmi', 5, 2)->nullable();
            $table->string('oxygen_saturation', 10)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'patient_id']);
            $table->index(['company_id', 'appointment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vital_signs');
    }
};
