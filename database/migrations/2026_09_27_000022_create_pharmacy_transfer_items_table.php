<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained('pharmacy_transfers')->cascadeOnDelete();
            $table->foreignId('medication_id')->constrained('pharmacy_medications')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('pharmacy_batches')->cascadeOnDelete();
            $table->unsignedInteger('quantity_requested');
            $table->unsignedInteger('quantity_dispatched')->nullable();
            $table->unsignedInteger('quantity_received')->nullable();
            $table->timestamps();

            $table->index('transfer_id');
            $table->index(['medication_id', 'batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_transfer_items');
    }
};
