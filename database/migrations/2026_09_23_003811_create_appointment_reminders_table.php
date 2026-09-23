<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rule_id')->nullable()->constrained('appointment_reminder_rules')->nullOnDelete();
            $table->enum('channel', ['sms', 'email', 'whatsapp', 'push']);
            $table->timestamp('scheduled_for');
            $table->timestamp('sent_at')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed', 'cancelled'])->default('pending');
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            // One reminder per appointment/rule pair — reprocessing the due-reminder sweep must
            // never double-send.
            $table->unique(['appointment_id', 'rule_id'], 'appointment_reminder_unique');
            $table->index(['status', 'scheduled_for'], 'appointment_reminder_due_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_reminders');
    }
};
