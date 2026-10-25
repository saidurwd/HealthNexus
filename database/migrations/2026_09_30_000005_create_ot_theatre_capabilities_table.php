<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Free configurable capability strings (general/orthopedic/laparoscopic/...), never a
     * hardcoded enum — spec §11.
     */
    public function up(): void
    {
        Schema::create('ot_theatre_capabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theatre_id')->constrained('ot_theatres')->cascadeOnDelete();
            $table->string('capability');
            $table->timestamps();

            $table->unique(['theatre_id', 'capability']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_theatre_capabilities');
    }
};
