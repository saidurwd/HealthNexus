<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vital_signs', function (Blueprint $table) {
            $table->foreignId('encounter_id')->nullable()->after('appointment_id')->constrained()->nullOnDelete();
            $table->index(['encounter_id']);
        });

        Schema::table('diagnoses', function (Blueprint $table) {
            $table->foreignId('encounter_id')->nullable()->after('appointment_id')->constrained()->nullOnDelete();
            $table->index(['encounter_id']);
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->foreignId('encounter_id')->nullable()->after('appointment_id')->constrained()->nullOnDelete();
            $table->index(['encounter_id']);
        });

        Schema::table('investigation_orders', function (Blueprint $table) {
            $table->foreignId('encounter_id')->nullable()->after('appointment_id')->constrained()->nullOnDelete();
            $table->index(['encounter_id']);
        });
    }

    public function down(): void
    {
        Schema::table('investigation_orders', function (Blueprint $table) {
            $table->dropForeign(['encounter_id']);
            $table->dropIndex(['encounter_id']);
            $table->dropColumn(['encounter_id']);
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropForeign(['encounter_id']);
            $table->dropIndex(['encounter_id']);
            $table->dropColumn(['encounter_id']);
        });

        Schema::table('diagnoses', function (Blueprint $table) {
            $table->dropForeign(['encounter_id']);
            $table->dropIndex(['encounter_id']);
            $table->dropColumn(['encounter_id']);
        });

        Schema::table('vital_signs', function (Blueprint $table) {
            $table->dropForeign(['encounter_id']);
            $table->dropIndex(['encounter_id']);
            $table->dropColumn(['encounter_id']);
        });
    }
};
