<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('pharmacy_orders')->cascadeOnDelete();
            $table->foreignId('prescription_item_id')->constrained('prescription_items')->cascadeOnDelete();
            $table->foreignId('medication_id')->nullable()->constrained('pharmacy_medications')->nullOnDelete();
            $table->string('requested_medicine_name');
            $table->unsignedInteger('quantity_prescribed')->nullable();
            $table->unsignedInteger('quantity_dispensed')->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index('medication_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_order_items');
    }
};
