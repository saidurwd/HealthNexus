<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Alert Service store (guideline 10.5, H-04): raise → deliver → acknowledge → escalate → resolve. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_alerts', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 40);                       // critical_lab, critical_radiology, icu_deterioration, nursing_ews ...
            $table->string('severity', 10);                       // info, warning, critical
            $table->string('message_key', 100)->nullable();
            $table->text('message');
            $table->string('source_module', 20);
            $table->string('source_type', 100)->nullable();
            $table->string('source_id', 40)->nullable();
            $table->string('status', 20)->default('open');       // open, acknowledged, resolved, overridden
            $table->boolean('ack_required')->default(true);
            $table->string('escalation_policy', 40)->nullable();
            $table->unsignedSmallInteger('escalation_level')->default(0);
            $table->timestamp('next_escalation_at')->nullable();
            $table->string('dedup_key', 120)->nullable();
            $table->timestamp('raised_at');
            $table->foreignId('raised_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('read_back_confirmed')->default(false);
            $table->text('acknowledge_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolve_note')->nullable();
            $table->text('override_reason')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status', 'severity'], 'idx_clin_alerts_inbox');
            $table->index(['status', 'next_escalation_at'], 'idx_clin_alerts_escalation');
            $table->index(['organization_id', 'dedup_key', 'status'], 'idx_clin_alerts_dedup');
            $table->unique(['source_module', 'source_type', 'source_id', 'category'], 'uq_clin_alerts_source');
        });

        Schema::create('clinical_alert_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_id')->constrained('clinical_alerts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('level')->default(0);
            $table->string('channels', 60)->nullable();
            $table->timestamp('notified_at');
            $table->timestamps();

            $table->unique(['alert_id', 'user_id'], 'uq_clin_alert_recipient');
            $table->index(['user_id', 'alert_id']);
        });

        // Append-only ladder history.
        Schema::create('clinical_alert_escalations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_id')->constrained('clinical_alerts')->cascadeOnDelete();
            $table->unsignedSmallInteger('level');
            $table->string('target_roles', 200)->nullable();
            $table->unsignedSmallInteger('recipients_notified')->default(0);
            $table->timestamp('escalated_at');

            $table->unique(['alert_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_alert_escalations');
        Schema::dropIfExists('clinical_alert_recipients');
        Schema::dropIfExists('clinical_alerts');
    }
};
