<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_critical_result_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('result_id')->constrained('lab_results')->cascadeOnDelete();
            $table->timestamp('detected_at');
            $table->foreignId('notified_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notification_method')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'acknowledged_at'], 'lab_crit_alerts_scope_ack_idx');
            $table->index(['result_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_critical_result_alerts');
    }
};
