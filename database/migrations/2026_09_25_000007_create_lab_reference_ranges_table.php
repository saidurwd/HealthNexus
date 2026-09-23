<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_reference_ranges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('lab_tests')->cascadeOnDelete();
            $table->foreignId('specimen_type_id')->nullable()->constrained('lab_specimen_types')->nullOnDelete();
            // male|female|any
            $table->string('gender')->default('any');
            $table->unsignedInteger('age_min_years')->nullable();
            $table->unsignedInteger('age_max_years')->nullable();
            // any|pregnant|not_pregnant
            $table->string('pregnancy_status')->default('any');
            $table->string('unit')->nullable();
            $table->decimal('low', 18, 4)->nullable();
            $table->decimal('high', 18, 4)->nullable();
            $table->string('text_range')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['test_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_reference_ranges');
    }
};
