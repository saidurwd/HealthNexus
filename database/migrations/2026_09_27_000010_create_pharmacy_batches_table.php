<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('medication_id')->constrained('pharmacy_medications')->cascadeOnDelete();
            $table->string('batch_number');
            $table->string('manufacturer')->nullable();
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date');
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->decimal('selling_price', 12, 2)->nullable();
            $table->string('supplier_reference')->nullable();
            $table->boolean('is_quarantined')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'medication_id', 'batch_number']);
            $table->index(['company_id', 'branch_id', 'medication_id']);
            $table->index('expiry_date');
            $table->index('is_quarantined');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_batches');
    }
};
