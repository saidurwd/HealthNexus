<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_critical_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_id')->constrained('radiology_reports')->cascadeOnDelete();
            $table->text('finding_text');
            $table->foreignId('detected_by')->nullable()->constrained('providers')->nullOnDelete();
            $table->timestamp('detected_at');
            $table->foreignId('notified_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notification_method')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('notes')->nullable();
            // detected|pending_notification|notified|acknowledged|escalated|closed
            $table->string('status')->default('detected');
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'status']);
            $table->index(['report_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_critical_findings');
    }
};
