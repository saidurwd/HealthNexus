<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('billing_categories')->nullOnDelete();
            $table->foreignId('tax_category_id')->nullable()->constrained('billing_tax_categories')->nullOnDelete();
            $table->string('item_code');
            $table->string('item_type')->default('service');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit')->default('each');
            $table->decimal('base_price', 18, 2)->default(0);
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_clinically_chargeable')->default(false);
            $table->string('clinical_event_type')->nullable();
            $table->string('clinical_event_key')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'branch_id', 'item_code']);
            $table->index(['company_id', 'branch_id', 'is_active']);
            $table->index(['clinical_event_type', 'clinical_event_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_items');
    }
};
