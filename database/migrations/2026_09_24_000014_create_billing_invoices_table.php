<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('corporate_id')->nullable()->constrained('billing_corporates')->nullOnDelete();
            $table->foreignId('corporate_contract_id')->nullable()->constrained('billing_corporate_contracts')->nullOnDelete();
            $table->foreignId('insurance_policy_id')->nullable()->constrained('billing_insurance_policies')->nullOnDelete();
            $table->string('invoice_number')->nullable();
            $table->string('invoice_type')->default('opd');
            $table->string('status')->default('draft');
            $table->string('currency', 3)->default('BDT');
            $table->string('patient_category')->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->string('discount_type')->default('none');
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('rounding_amount', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->decimal('due_amount', 18, 2)->default(0);
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('billing_party_type')->nullable();
            $table->unsignedBigInteger('billing_party_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('refunded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('refunded_at')->nullable();
            $table->foreignId('written_off_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('written_off_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'invoice_number']);
            $table->index(['company_id', 'branch_id', 'status', 'invoice_date']);
            $table->index(['patient_id', 'status']);
            $table->index(['corporate_id', 'status']);
            $table->index(['insurance_policy_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_invoices');
    }
};
