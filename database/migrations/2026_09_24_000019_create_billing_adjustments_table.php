<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('billing_invoices')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('billing_payments')->nullOnDelete();
            $table->string('adjustable_type')->nullable();
            $table->unsignedBigInteger('adjustable_id')->nullable();
            $table->string('type');
            $table->string('currency', 3)->default('BDT');
            $table->decimal('original_value', 18, 2);
            $table->decimal('new_value', 18, 2);
            $table->decimal('difference', 18, 2);
            $table->text('reason');
            $table->string('status')->default('requested');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('requested_at');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_note')->nullable();
            $table->timestamps();

            $table->index(['adjustable_type', 'adjustable_id']);
            $table->index(['company_id', 'branch_id', 'invoice_id', 'status']);
            $table->index(['payment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_adjustments');
    }
};
