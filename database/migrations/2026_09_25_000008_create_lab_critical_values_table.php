<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_critical_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('lab_tests')->cascadeOnDelete();
            $table->decimal('low_threshold', 18, 4)->nullable();
            $table->decimal('high_threshold', 18, 4)->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['test_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_critical_values');
    }
};
