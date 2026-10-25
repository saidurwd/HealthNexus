<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('icu_alert_escalations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_id')->constrained('icu_critical_alerts')->cascadeOnDelete();
            $table->string('escalated_to')->nullable();
            $table->foreignId('escalated_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('escalated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('escalated_at');
            $table->text('action_taken')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['alert_id', 'escalated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icu_alert_escalations');
    }
};
