<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_controlled_drug_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('store_id')->constrained('pharmacy_stores')->cascadeOnDelete();
            $table->foreignId('medication_id')->constrained('pharmacy_medications')->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('pharmacy_batches')->nullOnDelete();
            $table->string('type');
            $table->enum('direction', ['in', 'out']);
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('balance_after');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('performed_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('witnessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'medication_id', 'created_at'], 'pharmacy_cd_txn_scope_time_idx');
            $table->index(['reference_type', 'reference_id'], 'pharmacy_cd_txn_reference_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_controlled_drug_transactions');
    }
};
