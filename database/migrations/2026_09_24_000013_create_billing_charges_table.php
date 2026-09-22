<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('billing_item_id')->constrained('billing_items')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('users')->nullOnDelete();
            $table->nullableMorphs('source');
            $table->string('idempotency_key');
            $table->string('status')->default('pending');
            $table->unsignedInteger('quantity')->default(1);
            $table->string('currency', 3)->default('BDT');
            $table->decimal('unit_price', 18, 2);
            $table->decimal('gross_amount', 18, 2);
            $table->string('discount_type')->default('none');
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('net_amount', 18, 2);
            $table->timestamp('charged_at')->nullable();
            $table->timestamp('billed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'idempotency_key']);
            $table->index(['company_id', 'branch_id', 'patient_id', 'status'], 'billing_charges_scope_idx');
            $table->index(['billing_item_id', 'charged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_charges');
    }
};
