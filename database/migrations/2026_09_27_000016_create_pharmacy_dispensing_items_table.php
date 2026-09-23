<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_dispensing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispensing_id')->constrained('pharmacy_dispensings')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('pharmacy_order_items')->cascadeOnDelete();
            $table->foreignId('medication_id')->constrained('pharmacy_medications')->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('pharmacy_batches')->nullOnDelete();
            $table->unsignedInteger('quantity_prescribed')->nullable();
            $table->unsignedInteger('quantity_dispensed');
            $table->string('unit')->nullable();
            $table->boolean('substitution_flag')->default(false);
            $table->text('substitution_reason')->nullable();
            $table->foreignId('substituted_from_medication_id')->nullable()->constrained('pharmacy_medications')->nullOnDelete();
            $table->timestamps();

            $table->index('dispensing_id');
            $table->index('order_item_id');
            $table->index('batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_dispensing_items');
    }
};
