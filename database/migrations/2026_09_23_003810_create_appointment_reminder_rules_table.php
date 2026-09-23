<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_reminder_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('appointment_type_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('channel', ['sms', 'email', 'whatsapp', 'push']);
            // Minutes before the appointment start time — never hardcode "24 hours" (per spec).
            $table->unsignedInteger('offset_minutes');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'is_active'], 'appointment_reminder_rule_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_reminder_rules');
    }
};
