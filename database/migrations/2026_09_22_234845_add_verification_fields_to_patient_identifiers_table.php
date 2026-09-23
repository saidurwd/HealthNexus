<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_identifiers', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->after('is_primary');
            $table->foreignId('verified_by')->nullable()->after('is_verified')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
        });
    }

    public function down(): void
    {
        Schema::table('patient_identifiers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn(['is_verified', 'verified_at']);
        });
    }
};
