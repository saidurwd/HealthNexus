<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_corporate_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('corporate_id')->constrained('billing_corporates')->cascadeOnDelete();
            $table->foreignId('price_list_id')->nullable()->constrained('billing_price_lists')->nullOnDelete();
            $table->string('name');
            $table->string('discount_type')->default('none');
            $table->decimal('discount_value', 18, 2)->default(0);
            $table->decimal('credit_limit', 18, 2)->default(0);
            $table->unsignedSmallInteger('payment_terms_days')->default(30);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'corporate_id', 'status'], 'billing_corporate_contracts_scope_idx');
            $table->index(['effective_from', 'effective_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_corporate_contracts');
    }
};
