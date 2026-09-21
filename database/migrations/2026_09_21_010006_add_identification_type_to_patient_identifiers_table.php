<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_identifiers', function (Blueprint $table) {
            $table->foreignId('identification_type_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('patient_identifiers', function (Blueprint $table) {
            $table->dropForeign(['identification_type_id']);
            $table->dropColumn(['identification_type_id']);
        });
    }
};
