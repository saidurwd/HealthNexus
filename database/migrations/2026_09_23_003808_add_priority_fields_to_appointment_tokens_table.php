<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The token/queue concept is deliberately consolidated onto appointment_tokens rather
        // than adding a separate appointment_queue table (spec explicitly allows consolidation
        // "if it makes the scheduling engine unnecessarily complex" otherwise).
        Schema::table('appointment_tokens', function (Blueprint $table) {
            $table->enum('priority', ['emergency', 'priority', 'vip', 'regular', 'follow_up'])->default('regular')->after('status');
            $table->timestamp('skipped_at')->nullable()->after('completed_at');
            $table->foreignId('transferred_to_provider_id')->nullable()->after('skipped_at')->constrained('providers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointment_tokens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transferred_to_provider_id');
            $table->dropColumn(['priority', 'skipped_at']);
        });
    }
};
