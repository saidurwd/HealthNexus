<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The spec explicitly requires diagnosis coding to not be hardcoded to one system
     * (ICD-10/ICD-11/SNOMED CT/other), plus primary/secondary/differential/historical typing —
     * neither existed on this table despite both stacks (OPD + Clinical module) sharing it.
     */
    public function up(): void
    {
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->string('coding_system')->nullable()->after('code_type');
            $table->enum('diagnosis_type', ['primary', 'secondary', 'differential', 'historical'])->default('primary')->after('status');
            $table->boolean('is_primary')->default(false)->after('diagnosis_type');
        });
    }

    public function down(): void
    {
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->dropColumn(['coding_system', 'diagnosis_type', 'is_primary']);
        });
    }
};
