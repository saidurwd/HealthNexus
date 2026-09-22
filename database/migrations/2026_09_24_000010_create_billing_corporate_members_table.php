<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_corporate_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('corporate_id')->constrained('billing_corporates')->cascadeOnDelete();
            $table->foreignId('corporate_contract_id')->nullable()->constrained('billing_corporate_contracts')->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('member_number')->nullable();
            $table->string('employee_id')->nullable();
            $table->string('relationship')->default('employee');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['corporate_id', 'member_number']);
            $table->index(['company_id', 'branch_id', 'patient_id', 'is_active'], 'billing_corporate_members_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_corporate_members');
    }
};
