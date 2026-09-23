<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Row-locked counter, same pattern as patient_number_counters/appointment_number_counters —
     * replaces the unsafe orderByDesc('id')->id+1 pattern found in
     * EncounterClinicalService::createOrder() and OpdService::createPrescription().
     */
    public function up(): void
    {
        Schema::create('clinical_number_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('counter_type', ['clinical_order', 'prescription']);
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'branch_id', 'counter_type'], 'clinical_number_counter_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_number_counters');
    }
};
