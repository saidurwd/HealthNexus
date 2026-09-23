<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('consent_type');
            $table->unsignedInteger('version')->default(1);
            $table->enum('status', ['active', 'withdrawn'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('document_file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('granted_at');
            $table->foreignId('withdrawn_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();

            // Consents are versioned and never overwritten — each grant/withdrawal is a new row,
            // so history stays intact for audit/legal purposes.
            $table->index(['company_id', 'patient_id', 'consent_type'], 'patient_consent_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_consents');
    }
};
