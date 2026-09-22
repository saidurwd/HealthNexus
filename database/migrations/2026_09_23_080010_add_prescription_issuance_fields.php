<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('advice');
            $table->timestamp('issued_at')->nullable()->after('status');
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete()->after('issued_at');
        });
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropForeign(['issued_by']);
            $table->dropColumn(['status', 'issued_at', 'issued_by']);
        });
    }
};
