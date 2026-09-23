<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_quarantine', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('store_id')->constrained('pharmacy_stores')->cascadeOnDelete();
            $table->foreignId('medication_id')->constrained('pharmacy_medications')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('pharmacy_batches')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('reason_type');
            $table->text('reason');
            $table->string('status')->default('quarantined');
            $table->foreignId('quarantined_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('quarantined_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('disposed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disposed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'status']);
            $table->index(['store_id', 'medication_id', 'batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_quarantine');
    }
};
