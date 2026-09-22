<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('billing_invoices')->nullOnDelete();
            $table->foreignId('corporate_id')->nullable()->constrained('billing_corporates')->nullOnDelete();
            $table->foreignId('payment_method_id')->constrained('billing_payment_methods')->cascadeOnDelete();
            $table->foreignId('cashier_session_id')->nullable();
            $table->string('payment_number');
            $table->string('currency', 3)->default('BDT');
            $table->decimal('amount', 18, 2);
            $table->string('transaction_reference')->nullable();
            $table->date('payment_date');
            $table->string('status')->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('received_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'payment_number']);
            $table->index(['company_id', 'branch_id', 'patient_id', 'status', 'payment_date'], 'billing_payments_scope_idx');
            $table->index(['invoice_id', 'status']);
            $table->index(['cashier_session_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_payments');
    }
};
