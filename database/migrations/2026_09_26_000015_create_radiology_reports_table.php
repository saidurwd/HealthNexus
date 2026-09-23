<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radiology_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('examination_id')->constrained('radiology_examinations')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('radiology_report_templates')->nullOnDelete();
            $table->string('report_number');
            // draft|submitted|under_review|approved|final|amended|cancelled
            $table->string('status')->default('draft');
            $table->foreignId('radiologist_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->text('clinical_indication')->nullable();
            $table->text('technique')->nullable();
            $table->longText('findings')->nullable();
            $table->longText('impression')->nullable();
            $table->longText('recommendation')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('providers')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('providers')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            // Immutable versioning — an amendment creates a new row (version+1, amended_from_id
            // pointing at the row it supersedes) and flips the prior row's is_current to false.
            // Mirrors Laboratory's lab_results versioning exactly (plan decision #7).
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_current')->default(true);
            $table->foreignId('amended_from_id')->nullable()->constrained('radiology_reports')->nullOnDelete();
            $table->text('amendment_reason')->nullable();
            $table->foreignId('amended_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // An amendment intentionally reuses the same report_number across versions (it's the
            // same report, "v2" of it) — the report's identity is (company_id, report_number,
            // version), never report_number alone.
            $table->unique(['company_id', 'report_number', 'version']);
            $table->index(['examination_id', 'is_current']);
            $table->index(['company_id', 'branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_reports');
    }
};
