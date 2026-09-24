<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ipd_patient_leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admission_id')->constrained('ipd_admissions')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('leave_type')->default('temporary_pass');
            $table->timestamp('requested_at');
            $table->timestamp('expected_return_at');
            $table->timestamp('actual_return_at')->nullable();
            $table->text('reason')->nullable();
            $table->string('status')->default('requested');
            $table->string('bed_handling')->default('retain');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'status']);
            $table->index('admission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipd_patient_leaves');
    }
};
