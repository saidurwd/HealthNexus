<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_stock_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_count_id')->constrained('pharmacy_stock_counts')->cascadeOnDelete();
            $table->foreignId('medication_id')->constrained('pharmacy_medications')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('pharmacy_batches')->cascadeOnDelete();
            $table->unsignedInteger('expected_quantity');
            $table->unsignedInteger('counted_quantity')->nullable();
            $table->integer('variance')->nullable();
            $table->boolean('is_adjusted')->default(false);
            $table->timestamps();

            $table->index('stock_count_id');
            $table->unique(['stock_count_id', 'medication_id', 'batch_id'], 'pharmacy_stock_count_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_stock_count_items');
    }
};
