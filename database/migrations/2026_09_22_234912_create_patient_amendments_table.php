<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_amendments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_instance_id')->nullable()->constrained('workflow_instances')->nullOnDelete();
            $table->json('proposed_changes');
            $table->json('original_values')->nullable();
            $table->text('reason');
            $table->enum('status', ['draft', 'submitted', 'pending_approval', 'approved', 'applied', 'rejected'])->default('draft');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'patient_id', 'status'], 'patient_amendment_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_amendments');
    }
};
