<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Encounter and prescription numbers are built from the FIRST THREE letters of the organization code, so two organizations whose codes
 * begin alike (DHA…, MAI…) produced the same number and the second insert failed on a database-wide unique index (found while building
 * the ED, which creates an encounter per visit). Numbers are only unique inside their organization (guideline 9.1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->dropUnique(['encounter_no']);
            $table->unique(['organization_id', 'encounter_no'], 'uq_encounters_org_number');
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropUnique(['prescription_no']);
            $table->unique(['organization_id', 'prescription_no'], 'uq_prescriptions_org_number');
        });
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropUnique('uq_prescriptions_org_number');
            $table->unique('prescription_no');
        });

        Schema::table('encounters', function (Blueprint $table) {
            $table->dropUnique('uq_encounters_org_number');
            $table->unique('encounter_no');
        });
    }
};
