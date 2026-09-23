<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_report_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('procedure_id')->nullable()->constrained('radiology_procedures')->nullOnDelete();
            $table->string('modality_type')->nullable();
            $table->string('code');
            $table->string('name');
            // Data-driven section list (Clinical History/Technique/Findings/Measurements/
            // Impression/Recommendation) — never hardcoded in PHP (spec §40).
            $table->json('sections')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'branch_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_report_templates');
    }
};
