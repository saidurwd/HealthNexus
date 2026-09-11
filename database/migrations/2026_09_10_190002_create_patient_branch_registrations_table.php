<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_branch_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('local_patient_no')->nullable();
            $table->timestamp('registered_at');
            $table->enum('status', ['active', 'inactive', 'transferred', 'discharged'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'patient_id', 'branch_id'], 'patient_branch_unique');
            $table->index(['company_id', 'branch_id', 'local_patient_no'], 'patient_branch_local_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_branch_registrations');
    }
};
