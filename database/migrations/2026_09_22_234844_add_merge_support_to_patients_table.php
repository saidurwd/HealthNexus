<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Laravel 11+ rebuilds the table natively for column changes unsupported in-place by a
        // given driver (e.g. SQLite), so Blueprint::change() no longer needs doctrine/dbal.
        Schema::table('patients', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive', 'deceased', 'merged'])->default('active')->change();
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->foreignId('merged_into_patient_id')->nullable()->after('status')->constrained('patients')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('merged_into_patient_id');
        });

        DB::table('patients')->where('status', 'merged')->update(['status' => 'inactive']);

        Schema::table('patients', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive', 'deceased'])->default('active')->change();
        });
    }
};
