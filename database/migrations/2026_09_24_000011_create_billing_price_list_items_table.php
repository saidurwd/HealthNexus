<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_price_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_list_id')->constrained('billing_price_lists')->cascadeOnDelete();
            $table->foreignId('billing_item_id')->constrained('billing_items')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('corporate_id')->nullable()->constrained('billing_corporates')->nullOnDelete();
            $table->foreignId('insurance_policy_id')->nullable()->constrained('billing_insurance_policies')->nullOnDelete();
            $table->string('patient_category')->nullable();
            $table->string('scope_hash', 40);
            $table->decimal('unit_price', 18, 2);
            $table->decimal('minimum_price', 18, 2)->nullable();
            $table->decimal('maximum_price', 18, 2)->nullable();
            $table->string('discount_type')->default('none');
            $table->decimal('discount_value', 18, 2)->default(0);
            $table->boolean('tax_included')->default(false);
            $table->unsignedSmallInteger('priority')->default(100);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['price_list_id', 'billing_item_id', 'scope_hash'], 'billing_price_list_items_scope_hash_unique');
            $table->index(['price_list_id', 'billing_item_id', 'is_active', 'priority'], 'billing_price_list_items_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_price_list_items');
    }
};
