<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_medication_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medication_id')->constrained('pharmacy_medications')->cascadeOnDelete();
            $table->foreignId('generic_id')->constrained('pharmacy_generics')->cascadeOnDelete();
            $table->string('strength')->nullable();
            $table->string('unit')->nullable();
            $table->timestamps();

            $table->unique(['medication_id', 'generic_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_medication_ingredients');
    }
};
