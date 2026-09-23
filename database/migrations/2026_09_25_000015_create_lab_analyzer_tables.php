<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Foundation only — mapping tables for a future real analyzer integration
 * (LabAnalyzerAdapterInterface), no live ASTM/HL7 protocol implemented here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_analyzers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('lab_sections')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('vendor')->nullable();
            // file|astm|hl7|manual
            $table->string('connection_type')->default('manual');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'branch_id', 'code']);
        });

        Schema::create('lab_analyzer_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analyzer_id')->constrained('lab_analyzers')->cascadeOnDelete();
            $table->foreignId('test_id')->constrained('lab_tests')->cascadeOnDelete();
            $table->string('analyzer_test_code');
            $table->timestamps();

            $table->unique(['analyzer_id', 'test_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_analyzer_tests');
        Schema::dropIfExists('lab_analyzers');
    }
};
