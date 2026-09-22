<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('encounter_status_histories', function (Blueprint $table) {
            $table->renameColumn('old_status', 'from_status');
            $table->renameColumn('new_status', 'to_status');
            $table->timestamp('changed_at')->nullable()->after('reason');
            $table->json('metadata')->nullable()->after('changed_at');
        });
    }

    public function down(): void
    {
        Schema::table('encounter_status_histories', function (Blueprint $table) {
            $table->renameColumn('from_status', 'old_status');
            $table->renameColumn('to_status', 'new_status');
            $table->dropColumn(['changed_at', 'metadata']);
        });
    }
};
