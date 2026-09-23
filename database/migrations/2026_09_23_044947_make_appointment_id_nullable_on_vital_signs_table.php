<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * vital_signs was the only one of the four legacy clinical tables (vital_signs, diagnoses,
 * prescriptions, investigation_orders) created with a required appointment_id — its three
 * siblings are nullable. That blocks every walk-in (appointment-less) encounter from ever
 * recording vitals, directly violating the "Appointment ≠ Encounter" rule.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vital_signs', function (Blueprint $table) {
            $table->foreignId('appointment_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('vital_signs', function (Blueprint $table) {
            $table->foreignId('appointment_id')->nullable(false)->change();
        });
    }
};
