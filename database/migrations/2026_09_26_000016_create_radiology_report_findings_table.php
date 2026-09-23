<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional structured findings, additive to the report's free-text findings — never mandatory
 * (spec §41/§42).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_report_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('radiology_reports')->cascadeOnDelete();
            $table->string('organ')->nullable();
            $table->text('finding_text');
            $table->decimal('measurement_value', 10, 3)->nullable();
            $table->string('measurement_unit')->nullable();
            $table->string('laterality')->nullable();
            $table->boolean('is_critical')->default(false);
            $table->timestamps();

            $table->index(['report_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_report_findings');
    }
};
