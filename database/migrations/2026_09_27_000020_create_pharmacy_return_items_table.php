<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('pharmacy_returns')->cascadeOnDelete();
            $table->foreignId('dispensing_item_id')->constrained('pharmacy_dispensing_items')->cascadeOnDelete();
            $table->unsignedInteger('quantity_returned');
            $table->boolean('returnable_to_stock')->default(false);
            $table->timestamps();

            $table->index('return_id');
            $table->index('dispensing_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_return_items');
    }
};
