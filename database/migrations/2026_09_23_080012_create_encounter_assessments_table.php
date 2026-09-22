<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encounter_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('assessment_type')->nullable();
            $table->text('findings')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['encounter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encounter_assessments');
    }
};
