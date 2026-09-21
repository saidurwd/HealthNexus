<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'started_at')) {
                $table->dateTime('started_at')->nullable()->after('actual_datetime');
            }
            if (!Schema::hasColumn('appointments', 'ended_at')) {
                $table->dateTime('ended_at')->nullable()->after('started_at');
            }
            if (!Schema::hasColumn('appointments', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'ended_at', 'deleted_at']);
        });
    }
};
