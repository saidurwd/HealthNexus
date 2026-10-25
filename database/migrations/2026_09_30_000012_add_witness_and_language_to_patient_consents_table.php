<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive columns so Phase 10 can reuse the existing patient_consents model/version-chain
     * for surgical consent rather than building a parallel ot_consents table (decision #2).
     */
    public function up(): void
    {
        Schema::table('patient_consents', function (Blueprint $table) {
            $table->string('witness_name')->nullable()->after('notes');
            $table->string('language')->nullable()->after('witness_name');
        });
    }

    public function down(): void
    {
        Schema::table('patient_consents', function (Blueprint $table) {
            $table->dropColumn(['witness_name', 'language']);
        });
    }
};
