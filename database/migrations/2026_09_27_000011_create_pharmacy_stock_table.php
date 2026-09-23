<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('pharmacy_stores')->cascadeOnDelete();
            $table->foreignId('medication_id')->constrained('pharmacy_medications')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('pharmacy_batches')->cascadeOnDelete();
            $table->unsignedInteger('quantity_available')->default(0);
            $table->timestamps();

            $table->unique(['store_id', 'medication_id', 'batch_id'], 'pharmacy_stock_unique_join');
            $table->index(['store_id', 'medication_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_stock');
    }
};
