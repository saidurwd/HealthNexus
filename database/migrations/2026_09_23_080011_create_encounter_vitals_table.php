<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encounter_vitals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at');
            $table->decimal('temperature', 5, 2)->nullable();
            $table->string('temperature_unit')->nullable();
            $table->integer('systolic')->nullable();
            $table->integer('diastolic')->nullable();
            $table->string('bp_unit')->nullable();
            $table->integer('pulse_rate')->nullable();
            $table->integer('respiratory_rate')->nullable();
            $table->decimal('height', 6, 2)->nullable();
            $table->decimal('weight', 6, 2)->nullable();
            $table->decimal('bmi', 5, 2)->nullable();
            $table->integer('oxygen_saturation')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['encounter_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encounter_vitals');
    }
};
