<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Document & Print Service (guideline 14.3, H-08). */
return new class extends Migration
{
    public function up(): void
    {
        // Admin-editable overrides of the shipped templates. A new version is a new row; the old one stays readable.
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->string('doc_type', 40);
            $table->unsignedSmallInteger('version')->default(1);
            $table->longText('body');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'hospital_id', 'doc_type', 'version'], 'uq_document_templates_version');
            $table->index(['organization_id', 'doc_type', 'is_active'], 'idx_document_templates_active');
        });

        // One row per print or reprint: who, what, which template version, and why a copy was needed.
        Schema::create('document_prints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('printed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('doc_type', 40);
            $table->string('document_ref', 100);
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('language', 8);
            $table->string('template_source', 20);                  // file or db
            $table->unsignedSmallInteger('template_version')->nullable();
            $table->unsignedSmallInteger('copy_number')->default(1);
            $table->string('reprint_reason')->nullable();
            $table->timestamp('printed_at');

            $table->index(['organization_id', 'doc_type', 'document_ref'], 'idx_document_prints_doc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_prints');
        Schema::dropIfExists('document_templates');
    }
};
