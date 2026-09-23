<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * providers/doctor_schedules/appointments all gained a plain (unconstrained) specialty_id
     * column early in the Phase 2 migration sequence, before the Clinical module's `specialties`
     * table exists. This adds the foreign key now that it does.
     */
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->foreign('specialty_id')->references('id')->on('specialties')->nullOnDelete();
        });

        Schema::table('doctor_schedules', function (Blueprint $table) {
            $table->foreign('specialty_id')->references('id')->on('specialties')->nullOnDelete();
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreign('specialty_id')->references('id')->on('specialties')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropForeign(['specialty_id']);
        });

        Schema::table('doctor_schedules', function (Blueprint $table) {
            $table->dropForeign(['specialty_id']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['specialty_id']);
        });
    }
};
