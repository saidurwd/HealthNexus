<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_insurance_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('insurance_provider_id')->constrained('billing_insurance_providers')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('policy_number');
            $table->string('member_number')->nullable();
            $table->string('group_number')->nullable();
            $table->string('authorization_reference')->nullable();
            $table->decimal('coverage_limit', 18, 2)->default(0);
            $table->decimal('copay_percentage', 8, 4)->default(0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['insurance_provider_id', 'policy_number'], 'billing_insurance_policies_provider_number_unique');
            $table->index(['company_id', 'branch_id', 'patient_id', 'status'], 'billing_insurance_policies_scope_idx');
            $table->index(['effective_from', 'effective_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_insurance_policies');
    }
};
