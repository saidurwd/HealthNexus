<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_problems', function (Blueprint $table) {
            $table->foreignId('encounter_id')->nullable()->after('source_encounter_id')->constrained()->nullOnDelete();
            $table->index(['encounter_id']);
        });
    }

    public function down(): void
    {
        Schema::table('patient_problems', function (Blueprint $table) {
            $table->dropForeign(['encounter_id']);
            $table->dropIndex(['encounter_id']);
            $table->dropColumn(['encounter_id']);
        });
    }
};
