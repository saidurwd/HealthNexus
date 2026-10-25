<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('icu_procedure_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->constrained('icu_episodes')->cascadeOnDelete();
            $table->foreignId('procedure_id')->constrained('icu_procedures')->cascadeOnDelete();
            $table->text('indication')->nullable();
            $table->text('findings')->nullable();
            $table->text('complications')->nullable();
            // Dialysis/CRRT parameters live here as JSON rather than a standalone subsystem
            // (spec §30's explicit instruction).
            $table->json('parameters')->nullable();
            $table->foreignId('consent_id')->nullable()->constrained('patient_consents')->nullOnDelete();
            $table->timestamp('performed_at');
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['episode_id', 'performed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icu_procedure_records');
    }
};
