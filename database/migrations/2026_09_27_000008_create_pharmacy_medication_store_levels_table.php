<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_medication_store_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medication_id')->constrained('pharmacy_medications')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('pharmacy_stores')->cascadeOnDelete();
            $table->unsignedInteger('minimum_stock')->default(0);
            $table->unsignedInteger('reorder_level')->default(0);
            $table->unsignedInteger('maximum_stock')->nullable();
            $table->timestamps();

            $table->unique(['medication_id', 'store_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_medication_store_levels');
    }
};
