<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_remittances', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payer_id')->constrained('insurance_payers')->cascadeOnDelete();
            $table->string('remittance_number', 40);
            $table->string('payer_reference', 80)->nullable(); // cheque / transfer / advice number
            $table->string('method', 20)->default('bank_transfer');
            $table->decimal('amount', 12, 2);
            $table->decimal('allocated_amount', 12, 2)->default(0);
            $table->date('received_on');
            $table->string('status', 20)->default('unallocated'); // unallocated | partly_allocated | allocated
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'remittance_number']);
            $table->index(['payer_id', 'status']);
        });

        Schema::create('insurance_remittance_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('remittance_id')->constrained('insurance_remittances')->cascadeOnDelete();
            $table->foreignId('claim_id')->constrained('insurance_claims')->cascadeOnDelete();
            $table->decimal('paid_amount', 12, 2);
            $table->decimal('adjustment_amount', 12, 2)->default(0); // payer's disallowance on this payment
            $table->string('adjustment_reason', 200)->nullable();
            $table->unsignedBigInteger('billing_payment_id')->nullable(); // the payment posted on the invoice
            $table->timestamps();

            $table->index('claim_id');
        });

        // A write-off of what the payer will not pay; it needs a second person's approval.
        Schema::create('insurance_writeoffs', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('claim_id')->constrained('insurance_claims')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('reason', 300);
            $table->string('status', 12)->default('requested'); // requested | approved | rejected
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['status']);
        });

        // Corporate credit billing: a period's invoices for one corporate client, gathered into a statement.
        Schema::create('corporate_batches', function (Blueprint $table) {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('corporate_id')->constrained('billing_corporates')->cascadeOnDelete();
            $table->foreignId('payer_id')->nullable()->constrained('insurance_payers')->nullOnDelete();
            $table->string('batch_number', 40);
            $table->date('period_from');
            $table->date('period_to');
            $table->unsignedSmallInteger('invoice_count')->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('status', 12)->default('open'); // open | issued | settled | cancelled
            $table->date('due_on')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'batch_number']);
            $table->index(['corporate_id', 'status']);
        });

        Schema::create('corporate_batch_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('corporate_batches')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('billing_invoices')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->unique('invoice_id'); // an invoice is on one statement only
        });
    }

    public function down(): void
    {
        foreach (['corporate_batch_invoices', 'corporate_batches', 'insurance_writeoffs', 'insurance_remittance_lines', 'insurance_remittances'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
